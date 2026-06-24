# config.py
# SSH-Tunnel-Konfiguration

# GPU-Server
# this is only example data - replace!
SSH_CONFIG = {
    "ssh_host": "1.1.1.1",
    "ssh_port": 22,
    "ssh_username": "root",
    "ssh_key": "~/.ssh/id_rsa"
}
# LIVE
# this is only example data - replace!
DB_CONFIG = {
    "db_socket": "/var/lib/mysql/mysql.sock",
    "db_user": "myuser",
    "db_password": "password",
    "db_name": "mydb"
}
