# OpenGRC Disaster Recovery Guide

## Overview

This guide provides comprehensive procedures for backing up, restoring, and recovering your OpenGRC system in case of disasters or data loss situations.

## Table of Contents

1. [Backup System Overview](#backup-system-overview)
2. [Creating Backups](#creating-backups)
3. [Backup Verification](#backup-verification)
4. [Disaster Recovery Procedures](#disaster-recovery-procedures)
5. [Restoration Procedures](#restoration-procedures)
6. [Monitoring and Maintenance](#monitoring-and-maintenance)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)

## Backup System Overview

OpenGRC includes a comprehensive backup and disaster recovery system with the following features:

- **Automated Backups**: Scheduled database and file backups
- **Multiple Storage Options**: Local storage, private storage, and Amazon S3
- **Encryption & Compression**: Optional encryption and compression for security and space efficiency
- **Backup Verification**: Automated integrity checking using checksums
- **Flexible Restoration**: Selective restoration of database and/or files
- **Retention Management**: Automated cleanup of expired backups

### Backup Types

1. **Database Backup**: Backs up only the database (recommended for regular backups)
2. **Full Backup**: Backs up both database and critical files
3. **Incremental Backup**: (Future feature) Backs up only changes since last backup

## Creating Backups

### Using the Admin Panel

1. Navigate to **System > Backup Logs** in the admin panel
2. Click **Create Database Backup** or **Create Full Backup**
3. Configure backup options:
   - **Backup Name**: Custom name (optional)
   - **Encryption**: Enable for sensitive data
   - **Compression**: Enable to save storage space
   - **Storage Driver**: Choose where to store the backup
   - **Retention Days**: How long to keep the backup
   - **Exclude Tables**: Tables to exclude from backup

### Using Command Line

#### Database Backup
```bash
php artisan backup:database [options]
```

**Options:**
- `--name=NAME`: Custom backup name
- `--encrypt`: Encrypt the backup
- `--no-compress`: Disable compression
- `--storage=DRIVER`: Storage driver (local, private, s3)
- `--retention=DAYS`: Retention period in days
- `--exclude-tables=TABLE`: Exclude specific tables

**Examples:**
```bash
# Basic database backup
php artisan backup:database

# Encrypted backup with custom name
php artisan backup:database --name="pre_upgrade_backup" --encrypt

# Backup excluding specific tables
php artisan backup:database --exclude-tables=sessions --exclude-tables=cache
```

#### Full Backup
```bash
php artisan backup:full [options]
```

**Additional Options for Full Backup:**
- `--backup-directories=DIR`: Directories to include
- `--exclude-patterns=PATTERN`: File patterns to exclude

**Examples:**
```bash
# Basic full backup
php artisan backup:full

# Full backup with custom directories
php artisan backup:full --backup-directories=storage/app/private --backup-directories=.env
```

### Automated Backups

Configure automated backups in **Settings > Backup & Recovery**:

1. **Enable Automated Backups**: Turn on scheduled backups
2. **Backup Schedule**: Choose frequency (hourly, daily, weekly, monthly)
3. **Default Backup Type**: Database or full backup
4. **Retention Period**: How long to keep backups
5. **Storage Configuration**: Where to store backups

## Backup Verification

### Automatic Verification

Enable automatic verification in settings:
- **Auto-Verify Backups**: Verify integrity after creation
- **Verification Schedule**: Regular integrity checks

### Manual Verification

#### Using Admin Panel
1. Go to **System > Backup Logs**
2. Click on a backup entry
3. Click **Verify Backup** button

#### Using Command Line
```bash
# Verify specific backup
php artisan backup:verify BACKUP_ID

# Verify all unverified backups
php artisan backup:verify --all

# Force re-verification of all backups
php artisan backup:verify --all --force
```

## Disaster Recovery Procedures

### Recovery Planning

Before disaster strikes, ensure you have:

1. **Recovery Point Objective (RPO)**: Maximum acceptable data loss
2. **Recovery Time Objective (RTO)**: Maximum acceptable downtime
3. **Emergency Contacts**: Key personnel and their contact information
4. **Backup Locations**: Where backups are stored and how to access them
5. **Recovery Environment**: Prepared systems for restoration

### Emergency Response Steps

1. **Assess the Situation**
   - Determine the scope of the disaster
   - Identify what data/systems are affected
   - Estimate potential data loss

2. **Secure the Environment**
   - Stop any ongoing processes that might corrupt data
   - Isolate affected systems
   - Document the current state

3. **Identify Recovery Options**
   - List available backups
   - Check backup integrity
   - Select the most appropriate backup

4. **Execute Recovery Plan**
   - Follow restoration procedures below
   - Test restored system functionality
   - Communicate status to stakeholders

## Restoration Procedures

### Pre-Restoration Checklist

- [ ] System is in a stable state
- [ ] Current data is backed up (if possible)
- [ ] Backup integrity is verified
- [ ] Downtime is scheduled and communicated
- [ ] Recovery team is assembled

### Database Restoration

#### Using Admin Panel
1. Navigate to **System > Backup Logs**
2. Find the backup to restore
3. Click **View** then **Restore Backup**
4. Select restoration options:
   - **Restore Database**: Check this option
   - **Restore Files**: For full backups only
   - **Overwrite Files**: If restoring files

#### Using Command Line
```bash
php artisan backup:restore BACKUP_ID [options]
```

**Options:**
- `--database-only`: Restore database only
- `--files-only`: Restore files only
- `--overwrite-files`: Overwrite existing files
- `--force`: Skip confirmation prompts

**Examples:**
```bash
# Restore database only
php artisan backup:restore 123 --database-only

# Full restoration with file overwrite
php artisan backup:restore 123 --overwrite-files

# Force restore without prompts
php artisan backup:restore 123 --force
```

### Post-Restoration Steps

1. **Clear Application Cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

2. **Restart Services**
   - Restart web server
   - Restart queue workers
   - Restart any background services

3. **Verify System Functionality**
   - Test user authentication
   - Check critical application features
   - Verify data integrity
   - Test integrations

4. **Update Configurations**
   - Update environment variables if needed
   - Reconfigure any changed settings
   - Update SSL certificates if expired

## Monitoring and Maintenance

### Regular Maintenance Tasks

#### Daily
- [ ] Check backup status in admin panel
- [ ] Review failed backup notifications
- [ ] Monitor storage usage

#### Weekly
- [ ] Verify recent backups
- [ ] Review backup logs for errors
- [ ] Test restoration procedure (on test environment)

#### Monthly
- [ ] Review and update disaster recovery plan
- [ ] Test full restoration procedure
- [ ] Audit backup retention policies
- [ ] Update emergency contact information

### Cleanup Expired Backups

#### Automatic Cleanup
Enable in **Settings > Backup & Recovery**:
- **Auto-Cleanup Expired Backups**: Enable automatic deletion
- **Cleanup Schedule**: How often to run cleanup

#### Manual Cleanup
```bash
# View what would be deleted (dry run)
php artisan backup:cleanup --dry-run

# Delete expired backups
php artisan backup:cleanup

# Delete backups older than specific days
php artisan backup:cleanup --older-than=90
```

## Troubleshooting

### Common Issues

#### Backup Creation Fails

**Error: "Database connection failed"**
- Check database configuration in `.env`
- Verify database service is running
- Check database user permissions

**Error: "Storage disk not accessible"**
- Verify storage configuration
- Check file permissions
- For S3: Verify credentials and bucket access

**Error: "Insufficient disk space"**
- Check available storage space
- Clean up old backups
- Consider using compression

#### Backup Verification Fails

**Error: "Checksum mismatch"**
- Backup file may be corrupted
- Storage system may have issues
- Create a new backup

**Error: "Backup file not found"**
- File may have been deleted
- Check storage configuration
- Verify backup path

#### Restoration Issues

**Error: "Cannot restore incomplete backup"**
- Only completed backups can be restored
- Check backup status in admin panel
- Create a new backup if needed

**Error: "Database restoration failed"**
- Check database permissions
- Verify database service is running
- Check available disk space

### Getting Help

If you encounter issues not covered in this guide:

1. Check application logs in `storage/logs/`
2. Review backup logs in the admin panel
3. Check system requirements and dependencies
4. Contact your system administrator
5. Consult OpenGRC documentation

## Best Practices

### Backup Strategy

1. **3-2-1 Rule**: Keep 3 copies of data, on 2 different media, with 1 offsite
2. **Regular Schedule**: Automate daily database backups, weekly full backups
3. **Test Regularly**: Verify backups and test restoration procedures monthly
4. **Monitor Closely**: Set up alerts for backup failures
5. **Document Everything**: Keep recovery procedures up to date

### Security

1. **Encrypt Sensitive Backups**: Always encrypt backups containing sensitive data
2. **Secure Storage**: Use secure storage locations with proper access controls
3. **Rotate Credentials**: Regularly update backup storage credentials
4. **Audit Access**: Monitor who accesses backup systems

### Performance

1. **Schedule Wisely**: Run backups during low-traffic periods
2. **Use Compression**: Enable compression to save storage space
3. **Exclude Unnecessary Data**: Don't backup cache, sessions, logs
4. **Monitor Resources**: Ensure backup processes don't impact system performance

### Compliance

1. **Data Retention**: Follow regulatory requirements for data retention
2. **Audit Trails**: Maintain logs of backup and restoration activities
3. **Access Controls**: Implement proper access controls for backup systems
4. **Documentation**: Keep disaster recovery procedures documented and current

## Emergency Contacts

Update this section with your organization's emergency contacts:

- **System Administrator**: [Name, Phone, Email]
- **Database Administrator**: [Name, Phone, Email]
- **IT Manager**: [Name, Phone, Email]
- **Backup Service Provider**: [Company, Phone, Email]

## Recovery Objectives

Update these based on your organization's requirements:

- **Recovery Point Objective (RPO)**: Maximum acceptable data loss (e.g., 1 hour)
- **Recovery Time Objective (RTO)**: Maximum acceptable downtime (e.g., 4 hours)
- **Critical Systems**: List systems that must be restored first
- **Business Impact**: Document impact of extended downtime

---

*This document should be reviewed and updated regularly to ensure accuracy and relevance to your OpenGRC deployment.* 