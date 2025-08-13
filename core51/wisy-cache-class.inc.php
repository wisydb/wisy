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
    
    // Implementation for File-based caching
    // Approach: Create one cache file in system temp directory that holds a serialized array and the cache keys (ckey)
    // are the array keys in the array that is being serialized
    private $framework;
    private $cacheFile;
    private $itemLifetimeSeconds;
    private $storeBlobs;
    
    function __construct(&$framework, $param) {
        $this->framework            = &$framework;
        $this->itemLifetimeSeconds  = isset($param['itemLifetimeSeconds']) ? intval($param['itemLifetimeSeconds']) : null;
        $this->storeBlobs           = isset($param['storeBlobs']) && $param['storeBlobs'] ? true : false;
        $this->cacheFile            = __DIR__."/../../filecache/".$_SERVER['SERVER_NAME']; // sys_get_temp_dir() . '/cache_file'; // You can specify a different path
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
        $cache = $this->loadCache();
        
        if (isset($cache[$ckey])) {
            $item = $cache[$ckey];
            if ($this->itemLifetimeSeconds > 0 && $item['cdateinserted'] < (time() - $this->itemLifetimeSeconds)) {
                unset($cache[$ckey]);
                $this->saveCache($cache);
                return "";
            }
            return $item['cvalue'];
        }
        
        return "";
    }
    
    function insert($ckey, $cvalue) {
        $ckey = $this->createKey($ckey);
        $cdateinserted = date("Y-m-d H:i:s");
        $cache = $this->loadCache();
        
        $cache[$ckey] = [
            'cvalue' => $cvalue,
            'cdateinserted' => strtotime($cdateinserted)
        ];
        
        $this->saveCache($cache);
    }
    
    function cleanup() {
        file_put_contents($this->cacheFile, serialize([]));
    }
    
    function deleteOldEntries() {
        $deleteIfOlder = time() - $this->itemLifetimeSeconds;
        $cache = $this->loadCache();
        foreach ($cache as $key => $item) {
            if ($item['cdateinserted'] < $deleteIfOlder) {
                unset($cache[$key]);
            }
        }
        $this->saveCache($cache);
    }
    
    private function loadCache() {
        if (file_exists($this->cacheFile)) {
            return unserialize(file_get_contents($this->cacheFile));
        }
        return [];
    }
    
    private function saveCache($cache) {
        file_put_contents($this->cacheFile, serialize($cache));
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