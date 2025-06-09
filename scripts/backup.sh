#!/bin/bash
# Bienetre Pharma Backup Script

# Configuration
BACKUP_DIR="/path/to/backups"
DB_NAME="bienetre_pharma"
DB_USER="bienetre_user"
DB_PASS="N3*WsMh),,8&gI=A"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Database backup
echo "Creating database backup..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db_backup_$DATE.sql"

# Files backup
echo "Creating files backup..."
tar -czf "$BACKUP_DIR/files_backup_$DATE.tar.gz" storage/ public/uploads/

# Remove old backups (keep last 30 days)
find "$BACKUP_DIR" -name "*.sql" -mtime +30 -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"