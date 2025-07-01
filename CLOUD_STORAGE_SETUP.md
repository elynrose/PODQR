# Cloud Storage Setup Guide

This guide will help you configure cloud storage (AWS S3) for all image uploads in your Laravel application.

## Prerequisites

1. AWS Account with S3 access
2. AWS Access Key ID and Secret Access Key
3. S3 Bucket created

## Step 1: Configure AWS S3

### 1.1 Create an S3 Bucket

1. Log into your AWS Console
2. Navigate to S3 service
3. Click "Create bucket"
4. Choose a unique bucket name (e.g., `your-app-name-images`)
5. Select your preferred region
6. Configure bucket settings:
   - **Block Public Access**: Uncheck "Block all public access" (since we need public read access for images)
   - **Bucket Versioning**: Optional
   - **Tags**: Optional
7. Click "Create bucket"

### 1.2 Configure Bucket Policy

After creating the bucket, you need to configure a bucket policy to allow public read access:

1. Go to your bucket → Permissions → Bucket Policy
2. Add the following policy (replace `your-bucket-name` with your actual bucket name):

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::your-bucket-name/*"
        }
    ]
}
```

### 1.3 Create IAM User (Optional but Recommended)

For better security, create a dedicated IAM user for your application:

1. Go to IAM → Users → Create User
2. Give it a name (e.g., `app-s3-user`)
3. Attach the `AmazonS3FullAccess` policy (or create a custom policy with minimal permissions)
4. Create Access Keys for this user

## Step 2: Update Environment Configuration

Update your `.env` file with the following AWS configuration:

```env
# Filesystem Configuration
FILESYSTEM_DISK=s3

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_aws_access_key_id
AWS_SECRET_ACCESS_KEY=your_aws_secret_access_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.us-east-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Important**: Replace the placeholder values with your actual AWS credentials and bucket information.

## Step 3: Verify Dependencies

The application already includes the necessary AWS SDK packages:

- `aws/aws-sdk-php`
- `league/flysystem-aws-s3-v3`

If you need to install them manually:

```bash
composer require aws/aws-sdk-php league/flysystem-aws-s3-v3
```

## Step 4: Test Configuration

### 4.1 Test S3 Connection

Run the following command to test your S3 configuration:

```bash
php artisan tinker
```

Then in the tinker console:

```php
Storage::disk('s3')->put('test.txt', 'Hello World');
echo Storage::disk('s3')->get('test.txt');
Storage::disk('s3')->delete('test.txt');
```

### 4.2 Test Image Upload

1. Go to your application
2. Try uploading an image (e.g., create a new design or upload a file to the wall)
3. Check if the image is stored in your S3 bucket
4. Verify the image URL is accessible

## Step 5: Migrate Existing Images

If you have existing images in local storage, you can migrate them to cloud storage:

### 5.1 Dry Run (Recommended First)

Run a dry run to see what would be migrated:

```bash
php artisan images:migrate-to-cloud --dry-run
```

This will show you all the files that would be migrated without actually moving them.

### 5.2 Actual Migration

Once you're satisfied with the dry run results, run the actual migration:

```bash
php artisan images:migrate-to-cloud
```

This will migrate:
- Design images (front, back, cover)
- Clothes type images (front, back)
- Wall post attachments
- QR code files
- DALL-E generated images

## Step 6: Verify Migration

After migration, verify that:

1. All images are accessible via their URLs
2. The application is working correctly
3. New uploads are going to S3
4. Old local files can be safely removed

## Troubleshooting

### Common Issues

1. **403 Forbidden Error**
   - Check your AWS credentials
   - Verify bucket permissions
   - Ensure bucket policy allows public read access

2. **Images Not Loading**
   - Check if the S3 URL is correct
   - Verify the bucket region matches your configuration
   - Ensure images were successfully uploaded to S3

3. **Migration Errors**
   - Check file permissions on local storage
   - Verify S3 bucket has write permissions
   - Check network connectivity

### Debug Commands

```bash
# Check S3 disk configuration
php artisan tinker
Storage::disk('s3')->exists('test');

# List files in S3 bucket
php artisan tinker
Storage::disk('s3')->files('designs');

# Test file upload
php artisan tinker
Storage::disk('s3')->put('test.txt', 'test content');
```

## Security Considerations

1. **Never commit AWS credentials to version control**
2. **Use IAM roles instead of access keys in production**
3. **Consider using CloudFront for better performance**
4. **Regularly rotate access keys**
5. **Monitor S3 usage and costs**

## Performance Optimization

1. **Enable CloudFront CDN** for faster image delivery
2. **Use appropriate image sizes** to reduce bandwidth
3. **Consider image compression** before upload
4. **Implement lazy loading** for image galleries

## Cost Optimization

1. **Monitor S3 usage** regularly
2. **Use S3 Lifecycle policies** to move old files to cheaper storage
3. **Consider S3 Intelligent Tiering** for automatic cost optimization
4. **Delete unused images** regularly

## Backup Strategy

1. **Enable S3 versioning** for important images
2. **Set up cross-region replication** for critical data
3. **Regularly backup your database** (image paths are stored there)
4. **Test your backup and restore procedures**

## Support

If you encounter issues:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify AWS credentials and permissions
3. Test S3 connectivity manually
4. Check the application logs for specific error messages

## Next Steps

After successful setup:

1. Monitor the application for any image-related issues
2. Set up monitoring and alerting for S3 usage
3. Consider implementing image optimization
4. Plan for scaling as your image storage grows 