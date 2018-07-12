# KanColle OpenDB Database backup

KanColle OpenDB is Transparent opened project.

Author WolfgangKurz has all copyrights of data, for to prevent data's unauthorized use.

This repository will be used for database dump backup only.

Backup files has compressed with pbzip2, and has splitted as 50m size.

Database will be backup as incremental.


## 📂 Restore database
### 1. Combine
Combining splitted data is very easy, not difficult.

Just use ```cat``` command with wildcard, it will return the combined contents.

### 2. Unzip (Decompressing)
All backup file was compressed with pbzip2, so ```bzip2``` or ```pbzip2``` is required to unzip backup file.

Using ```pbzip2``` is "highly" recommended.

### 3. Example
```bash
$ cat opendb-backup.sql.bz2.* | bzip2 -d > opendb-backup.sql
```

## 📈 Incremental backup
Backup files was dumped as incremental, using mysqldump and mysqlbinlog.

If you want to restore database, you need to restore all backups, and execute all restored queries.


## 📦 Utility
You can also use Backup and Restore utilities, written in PHP.

Use those utilities in terminal like below:
``` bash
$ php db-backup.php --help
$ php db-restore.php --help
```

Backup utility requires ```db-backup-cfg.php``` file and you can modify ```db-backup-cfg-template.php``` to get config file.


## 🔗 OpenDB Project Website
[KanColle OpenDB Website](http://swaytwig.com/opendb/)
