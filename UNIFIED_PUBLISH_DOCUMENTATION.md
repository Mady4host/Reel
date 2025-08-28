# Unified Publish Feature Documentation

## Overview
The Unified Publish feature provides a comprehensive solution for batch publishing content to Facebook and Instagram platforms. It includes a drag-and-drop interface, file management, scheduling capabilities, and integration with the existing cron system.

## Architecture

### Backend Components

#### 1. Publication Controller (`application/controllers/Publication.php`)
- **Main endpoint**: `/publication` - Displays the unified publish interface
- **API endpoint**: `/publication/create_batch` - Handles batch creation with file uploads
- **Features**:
  - File upload validation and processing
  - Batch and item creation
  - Integration with scheduled_reels table for cron processing
  - Support for immediate and scheduled publishing
  - Platform-specific handling (Facebook/Instagram)

#### 2. Publish Batches Model (`application/models/Publish_batches_model.php`)
- Manages `publish_batches` and `publish_batch_items` tables
- CRUD operations for batches and items
- Status tracking and statistics
- Batch deletion with cascading item removal

### Frontend Components

#### 3. Main View (`application/views/unified_publish.php`)
- Responsive Bootstrap 5 interface
- Platform selection (Facebook/Instagram)
- Batch configuration
- Page selection
- Dropzone integration for file uploads
- Real-time item management

#### 4. Item Partial (`application/views/partials/unified_publish_item.php`)
- Template for individual upload items
- File preview (video/image)
- Description editing
- Publish mode selection (immediate/scheduled)
- Schedule time picker
- Remove functionality

#### 5. JavaScript (`assets/js/unified_publish.js`)
- Dropzone.js integration
- File management and validation
- Form handling and API communication
- Real-time UI updates
- Error handling and user feedback

#### 6. CSS (`assets/css/unified_publish.css`)
- Custom styling for the interface
- Responsive design
- Dark mode support
- Animation and transitions
- File preview styling

### Language Support

#### 7. Language Files
- **Arabic**: `application/language/ar/unified_publish_lang.php`
- **English**: `application/language/en/unified_publish_lang.php`
- Comprehensive translations for all UI elements
- Error messages and help text

## Database Schema

### Tables (Assumed to exist)

#### publish_batches
```sql
CREATE TABLE publish_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform ENUM('facebook', 'instagram') NOT NULL,
    title VARCHAR(255) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

#### publish_batch_items
```sql
CREATE TABLE publish_batch_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    description TEXT,
    publish_mode ENUM('immediate', 'scheduled') DEFAULT 'immediate',
    scheduled_time TIMESTAMP NULL,
    media_kind ENUM('video', 'image', 'unknown') DEFAULT 'unknown',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES publish_batches(id) ON DELETE CASCADE,
    INDEX idx_batch_id (batch_id),
    INDEX idx_status (status)
);
```

## API Reference

### POST /publication/create_batch

#### Request
- **Content-Type**: `multipart/form-data`
- **Parameters**:
  - `platform` (string): "facebook" or "instagram"
  - `title` (string): Batch title
  - `items` (JSON string): Array of item configurations
  - `files[]` (files): Uploaded files (ordered)

#### Items JSON Structure
```json
[
  {
    "index": 0,
    "description": "Post description",
    "publish_mode": "scheduled",
    "scheduled_time": "2024-08-28T18:00:00"
  },
  {
    "index": 1,
    "description": "Another post",
    "publish_mode": "immediate",
    "scheduled_time": ""
  }
]
```

#### Response
```json
{
  "ok": true,
  "batch_id": 123,
  "created_items": [456, 789],
  "scheduled_created": 1,
  "errors": []
}
```

## File Management

### Upload Directory
- **Location**: `uploads/publish_batches/`
- **Naming**: `batch_{timestamp}_{index}_{random}_{safe_filename}`
- **Permissions**: 0775
- **Supported formats**: MP4, MOV, MKV, M4V, JPG, JPEG, PNG
- **Size limits**: 50KB minimum, 100MB maximum

### File Processing
1. Validation (size, format, errors)
2. Safe filename generation
3. Move to upload directory
4. Store relative path in database
5. Media type detection (video/image)

## Integration with Existing System

### Scheduled Reels Integration
For items with `publish_mode=scheduled`, the system creates entries in the existing `scheduled_reels` table:

```php
$scheduled_data = [
    'user_id' => $user_id,
    'fb_page_id' => $page_id,
    'video_path' => $file_path,
    'description' => $description,
    'scheduled_time' => $utc_time,
    'status' => 'pending',
    'attempt_count' => 0,
    'processing' => 0,
    'created_at' => gmdate('Y-m-d H:i:s')
];
```

This ensures compatibility with the existing cron job system that processes scheduled content.

## Configuration

### Controller Constants
```php
const UPLOAD_DIR = 'uploads/publish_batches/';
const ALLOWED_EXTENSIONS = ['mp4', 'mov', 'mkv', 'm4v', 'jpg', 'jpeg', 'png'];
const MIN_FILE_SIZE_BYTES = 50 * 1024; // 50KB
const MAX_FILE_SIZE_BYTES = 100 * 1024 * 1024; // 100MB
```

### JavaScript Configuration
```javascript
{
    maxFileSize: 100 * 1024 * 1024,
    createBatchUrl: '/publication/create_batch',
    allowedExtensions: ['mp4', 'mov', 'mkv', 'm4v', 'jpg', 'jpeg', 'png'],
    lang: { /* language strings */ }
}
```

## Security Considerations

1. **File Upload Security**:
   - Extension validation
   - Size limits
   - Safe filename generation
   - Directory traversal prevention

2. **Input Validation**:
   - XSS protection with `xss_clean()`
   - SQL injection prevention with CI's Query Builder
   - CSRF protection (requires CI setup)

3. **Access Control**:
   - User authentication required
   - User-specific data isolation
   - Page ownership verification

## Error Handling

### Client-Side
- File validation errors
- Network errors
- Form validation
- User-friendly messages

### Server-Side
- HTTP status codes
- JSON error responses
- Exception handling
- Database transaction safety

## Testing

### Test Files Included
- `test_unified_publish.php` - Backend verification
- `test_ui_preview.html` - Frontend preview

### Manual Testing Steps
1. Load `/publication` route
2. Select platform and configure batch
3. Upload files via drag-and-drop
4. Configure individual items
5. Submit batch creation
6. Verify database entries
7. Check scheduled_reels integration

## Deployment Checklist

- [ ] Deploy files to CodeIgniter application
- [ ] Create database tables
- [ ] Configure routing for `/publication`
- [ ] Set upload directory permissions
- [ ] Test file upload functionality
- [ ] Verify cron integration
- [ ] Test with actual Facebook/Instagram pages
- [ ] Configure language preferences
- [ ] Set up error logging

## Future Enhancements

1. **Account/Page Selector**: Per-item page selection
2. **Posting Logic**: Direct publishing integration
3. **Recurrence**: Recurring post scheduling
4. **Analytics**: Batch performance tracking
5. **Templates**: Pre-configured post templates
6. **Bulk Operations**: Mass edit capabilities
7. **Preview**: Content preview before publishing
8. **History**: Batch history and logs