<?php

if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 * WISY Caches
 ******************************************************************************
 * konkret gibt es die fogenden Caches:
 *
 * x_cache_search	dieser Cache wird komplett verworfen, wenn irgendwelche
 *                  Änderungen von der Redaktion vorgenommen werden; dieser
 *                  Cache ist also - zumindest in der Woche tagsüber - sehr
 *                  kurzlebig.  Außerdem wird der Cache bei den nächtlichen
 *                  Aufräumarbeiten komplett verworfen, damit Anfragen wie
 *                  "beginnt morgen" neu erzeugt werden können.
 * x_cache_rss		dieser Cache wird für RSS-Anfragen verwendet, er wird nicht
 *                  bei jeder Änderung der Redaktion verworfen, sondern nur
 *                  einmal nachts.
 ******************************************************************************
 * Details zu x_cache_search:
 *
 * eine Log-Auswertung via $framework->log() am 18.09.2009 brachte die folgende
 * Statistik:
 *
 * cleanups: 728  (wenn die WISY-Datenbank von der Redaktion geändert wird,
 *                wird der Cache verworfen)
 * inserts:  3801 (nach einer erfolgend Suche)
 * hits:     1780 (eine gesparte Suche)
 *
 * da ein Inserts und die Cleanups im Millisekonden bereich liegen, die Hits
 * aber im schnitt eine Sekunde dauern (speziell die häufigen Abfragen der
 * Startseite dauern etwas, wenn es einen Portalfilter gibt), lohnt sind das
 * ganze wohl:
 *
 * Rechnen wir mal mit 10ms je insert/cleanup
 *
 * "Ausgaben":     728+3801 * 10ms = 45290 ms = 45 Sekunden = <1 Minute
 * "Ersparnisse":  1780  * 1000ms = 1780000 ms = 1780 Sekunden = 29 Minute (!)
 ******************************************************************************/

interface CacheInterface {
    public function createKey($ckey);
    public function lookup($ckey);
    public function insert($ckey, $cvalue);
    public function cleanup();
    public function deleteOldEntries();
}

class DB_Cache implements CacheInterface {
    
    // Implementation for DB-based caching
    
    private $framework;
    private $table;
    private $db;
    private $itemLifetimeSeconds;
    private $storeBlobs;
    
    function __construct($framework, $param)
    {
        // constructor
        $this->framework			= $framework;
        $this->table				= isset($param['table']) ? $param['table'] : '';
        $this->itemLifetimeSeconds	= isset($param['itemLifetimeSeconds']) ? intval($param['itemLifetimeSeconds']) : null;
        $this->storeBlobs			= isset($param['storeBlobs']) && $param['storeBlobs'] ? true : false;
        $this->db 					= new DB_Admin();
    }
    
    function createKey($ckey)
    {
        $len = strlen($ckey);
        if( $len > 255 )
        {
            // otherwise, Mysql just truncates to 255 characters which may lead to duplicate entries, see
            // https://mail.google.com/mail/#all/13147e7e50122ead
            return substr($ckey, 0, 111) . md5(substr($ckey, 111, $len-111-112)) . substr($ckey, -112);
        }
        else
        {
            return $ckey;
        }
    }
    
    function lookup($ckey)
    {
        $ckey = $this->createKey($ckey);
        
        $this->db->query("SELECT cvalue, cdateinserted FROM $this->table WHERE ckey='".addslashes($ckey)."';");
        if( $this->db->next_record() )
        {
            if( $this->itemLifetimeSeconds > 0 )
            {
                $deleteIfOlder = ftime("%Y-%m-%d %H:%M:%S", time()-$this->itemLifetimeSeconds);
                if( $this->db->fcs8('cdateinserted') < $deleteIfOlder )
                {
                    $this->db->query("DELETE FROM $this->table WHERE cdateinserted<'$deleteIfOlder';");
                    return "";
                }
            }
            
            if( $this->storeBlobs )
                return $this->db->fcs8('cvalue');
                else
                    return $this->db->fcs8('cvalue');
        }
        
        return "";
    }
    
    
    function insert($ckey, $cvalue)
    {
        $ckey = $this->createKey($ckey);
        $cvalue = addslashes($cvalue);
        $ckey = addslashes($ckey);
        $cdateinserted = ftime("%Y-%m-%d %H:%M:%S");
        
        $query = "INSERT INTO $this->table (ckey, cvalue, cdateinserted) VALUES ('$ckey', '$cvalue', '$cdateinserted') ON DUPLICATE KEY UPDATE cvalue='$cvalue', cdateinserted='$cdateinserted';";
        
        @$this->db->query($query);
    }
    
    function cleanup()
    {
        $this->db->query("TRUNCATE TABLE $this->table;");
    }
    
    function deleteOldEntries()
    {
        $deleteIfOlder = ftime("%Y-%m-%d %H:%M:%S", time()-$this->itemLifetimeSeconds);
        $this->db->query("DELETE FROM $this->table WHERE cdateinserted<'$deleteIfOlder';");
    }
}

class Memcached_Cache implements CacheInterface {
    private $memcached;
    private $itemLifetimeSeconds;
    
    function __construct(&$framework, $param) {
        $this->itemLifetimeSeconds = isset($param['itemLifetimeSeconds']) ? intval($param['itemLifetimeSeconds']) : 0;
        
        $memcached_server   = is_object($framework) ? $framework->iniRead('cache.memcached.server', 'localhost') : 'localhost';
        $memcached_port     = is_object($framework) ? $framework->iniRead('cache.memcached.port', 11211) : 11211;
        
        
        $this->memcached = new Memcached();
        $this->memcached->addServer( $memcached_server, $memcached_port);
    }
    
    function createKey($ckey) {
        $len = strlen($ckey);
        if ($len > 255) {
            return substr($ckey, 0, 111) . md5(substr($ckey, 111, $len-111-112)) . substr($ckey, -112);
        } else {
            return $ckey;
        }
    }
    
    function lookup($ckey) {
        $ckey = $this->createKey($ckey);
        $value = $this->memcached->get($ckey);
        
        return $value !== false ? $value : "";
    }
    
    function insert($ckey, $cvalue) {
        $ckey = $this->createKey($ckey);
        $this->memcached->set($ckey, $cvalue, $this->itemLifetimeSeconds);
    }
    
    function cleanup() {
        $this->memcached->flush();
    }
    
    function deleteOldEntries() {
        ; // Memcached handles expiration based on the itemLifetimeSeconds, so no need to manually delete old entries
    }
}


class File_Cache implements CacheInterface {
    
    // File-based caching with one file per cache entry.
    //
    // The previous implementation kept all entries in a single serialized
    // array file. That suffered from a lost-update race: every insert did
    // read-modify-write on the whole file, so concurrent PHP processes would
    // overwrite each other's additions. It also rewrote the entire file on
    // every insert, which does not scale.
    //
    // Now each entry lives in its own "<sha256(key)>.cache" file inside the
    // cache directory. The key is hashed because raw cache keys may contain
    // characters that are unsafe or too long for filenames (URLs, search
    // queries, etc.); sha256 yields fixed-length, safe, effectively
    // collision-free filenames. Writes go through a temp file + rename, so
    // readers never observe a partially written entry. Expiry uses the
    // file's mtime; deleteOldEntries / cleanup just unlink the matching files.
    private $framework;
    private $cacheDir;
    private $itemLifetimeSeconds;
    private $storeBlobs;
    
    function __construct(&$framework, $param) {
        $this->framework            = &$framework;
        $this->itemLifetimeSeconds  = isset($param['itemLifetimeSeconds']) ? intval($param['itemLifetimeSeconds']) : null;
        $this->storeBlobs           = isset($param['storeBlobs']) && $param['storeBlobs'] ? true : false;
        $this->cacheDir             = __DIR__."/../../filecache/".$_SERVER['SERVER_NAME'];
        
        // Portal setting "cache.file.path": absolute or relative path to the
        // cache directory. Each cache entry becomes its own ".cache" file
        // inside this directory. Relative paths are resolved against the
        // project root (two levels above this file).
        $projectRoot = __DIR__ . '/../..';
        $configured  = is_object($framework) ? trim($framework->iniRead('cache.file.path', '')) : '';
        
        if ($configured !== '') {
            $isAbsolute = $configured[0] === '/'
                || $configured[0] === DIRECTORY_SEPARATOR
                || (strlen($configured) > 1 && $configured[1] === ':');
                $this->cacheDir = $isAbsolute ? $configured : $projectRoot . '/' . $configured;
        }
        
        // Backwards compatibility: The previous implementation wrote a single cache *file* at this
        // path. Remove it just in case so the path can be reused as a directory; its
        // contents were just regenerable cache entries.
        if (is_file($this->cacheDir)) {
            @unlink($this->cacheDir);
        }
        // Create cache directory if not exists
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
        
    }
    
    function createKey($ckey) {
        return hash('sha256', $ckey);
    }
    
    private function entryPath($ckey) {
        return $this->cacheDir . '/' . $ckey . '.cache';
    }
    
    function lookup($ckey) {
        $ckey = $this->createKey($ckey);
        $path = $this->entryPath($ckey);
        
        if (!is_file($path)) {
            return "";
        }
        
        if ($this->itemLifetimeSeconds > 0) {
            $mtime = @filemtime($path);
            if ($mtime !== false && $mtime < time() - $this->itemLifetimeSeconds) {
                @unlink($path);
                return "";
            }
        }
        
        $data = @file_get_contents($path);
        return $data === false ? "" : $data;
    }
    
    function insert($ckey, $cvalue) {
        $ckey = $this->createKey($ckey);
        $path = $this->entryPath($ckey);
        
        // Write to a per-process unique temp file then atomically rename, so
        // concurrent readers never see a partially written entry and parallel
        // writers do not corrupt each other's temp files.
        $tmp = $path . '.' . getmypid() . '.' . uniqid('', true) . '.tmp';
        if (@file_put_contents($tmp, $cvalue, LOCK_EX) === false) {
            return;
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
        }
    }
    
    function cleanup() {
        $files = @glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return;
        }
        foreach ($files as $f) {
            @unlink($f);
        }
    }
    
    function deleteOldEntries() {
        if ($this->itemLifetimeSeconds <= 0) {
            return;
        }
        $deleteIfOlder = time() - $this->itemLifetimeSeconds;
        $files = @glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return;
        }
        
        foreach ($files as $f) {
            $mtime = @filemtime($f);
            if ($mtime !== false && $mtime < $deleteIfOlder) {
                @unlink($f);
            }
        }
    }
}

class WISY_CACHE_CLASS {
    private $cache;
    
    function __construct(&$framework, $param) {
        
        $cacheType = is_object($framework) ? trim($framework->iniRead('cache.type', 'db')) : 'file';
        
        switch ($cacheType) {
            case 'db':
                $this->cache = new DB_Cache($framework, $param);
                break;
            case 'memcached':
                $this->cache = new Memcached_Cache($framework, $param);
                break;
            case 'file':
            default:
                $this->cache = new File_Cache($framework, $param);
                break;
        }
    }
    
    function lookup($ckey) {
        return $this->cache->lookup($ckey);
    }
    
    function insert($ckey, $cvalue) {
        $this->cache->insert($ckey, $cvalue);
    }
    
    function cleanup() {
        $this->cache->cleanup();
    }
    
    function deleteOldEntries() {
        $this->cache->deleteOldEntries();
    }
};