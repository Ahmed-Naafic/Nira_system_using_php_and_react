# File Upload Setup Guide

This guide explains how to set up file uploads for citizen images and documents.

## Database Setup

1. Run the database migration to add file path columns:
   ```sql
   mysql -u root -p nira_system < database/add_citizen_files.sql
   ```
   
   Or manually execute the SQL in `database/add_citizen_files.sql`

## Directory Permissions

The uploads directory will be created automatically, but ensure PHP has write permissions:

```bash
# On Linux/Mac
chmod -R 755 nira_backend/uploads
chown -R www-data:www-data nira_backend/uploads

# On Windows (XAMPP)
# Make sure the uploads directory has write permissions for the web server user
```

## File Upload Features

### Supported Image Formats
- JPEG/JPG
- PNG
- GIF
- WEBP
- Maximum size: 5MB

### Supported Document Formats
- PDF
- DOC
- DOCX
- JPEG/JPG (for scanned documents)
- PNG (for scanned documents)
- Maximum size: 10MB

## File Storage

Files are stored in:
- `nira_backend/uploads/images/` - Citizen photos
- `nira_backend/uploads/documents/` - Supporting documents

File naming format: `{nationalId}_{timestamp}_{random}.{ext}`

## Security

- Files are served through `/api/files/get.php` which requires authentication
- File types are validated by MIME type
- File sizes are limited
- Directory traversal is prevented
- PHP files in uploads directory are blocked

## API Usage

### Frontend (FormData)
```javascript
const formData = new FormData();
formData.append('firstName', 'John');
formData.append('image', imageFile);
formData.append('document', documentFile);

await api.post('/api/citizens/create.php', formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
  },
});
```

### Backend Response
The citizen object will include:
- `imagePath` - Relative path to image
- `imageUrl` - Full URL to access image
- `documentPath` - Relative path to document
- `documentUrl` - Full URL to access document

## Troubleshooting

### Upload fails with "Permission denied"
- Check directory permissions
- Ensure uploads directory exists and is writable

### File not found after upload
- Check file path in database
- Verify file exists in uploads directory
- Check .htaccess rules

### File too large
- Check PHP `upload_max_filesize` and `post_max_size` in php.ini
- Increase limits if needed:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 12M
  ```
