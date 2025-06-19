<?php

namespace Database\Seeders;

use App\Models\FileTypeSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FileTypeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fileTypes = [
            // Images
            ['extension' => 'jpg', 'mime_type' => 'image/jpeg', 'display_name' => 'JPEG Image', 'category' => 'image', 'priority' => 100],
            ['extension' => 'jpeg', 'mime_type' => 'image/jpeg', 'display_name' => 'JPEG Image', 'category' => 'image', 'priority' => 99],
            ['extension' => 'png', 'mime_type' => 'image/png', 'display_name' => 'PNG Image', 'category' => 'image', 'priority' => 98],
            ['extension' => 'gif', 'mime_type' => 'image/gif', 'display_name' => 'GIF Image', 'category' => 'image', 'priority' => 97],
            ['extension' => 'webp', 'mime_type' => 'image/webp', 'display_name' => 'WebP Image', 'category' => 'image', 'priority' => 96],
            ['extension' => 'svg', 'mime_type' => 'image/svg+xml', 'display_name' => 'SVG Image', 'category' => 'image', 'priority' => 95],
            ['extension' => 'ico', 'mime_type' => 'image/x-icon', 'display_name' => 'Icon', 'category' => 'image', 'priority' => 94],
            
            // Videos
            ['extension' => 'mp4', 'mime_type' => 'video/mp4', 'display_name' => 'MP4 Video', 'category' => 'video', 'priority' => 90],
            ['extension' => 'avi', 'mime_type' => 'video/x-msvideo', 'display_name' => 'AVI Video', 'category' => 'video', 'priority' => 89],
            ['extension' => 'mov', 'mime_type' => 'video/quicktime', 'display_name' => 'QuickTime Video', 'category' => 'video', 'priority' => 88],
            ['extension' => 'wmv', 'mime_type' => 'video/x-ms-wmv', 'display_name' => 'Windows Media Video', 'category' => 'video', 'priority' => 87],
            ['extension' => 'webm', 'mime_type' => 'video/webm', 'display_name' => 'WebM Video', 'category' => 'video', 'priority' => 86],
            
            // Audio
            ['extension' => 'mp3', 'mime_type' => 'audio/mpeg', 'display_name' => 'MP3 Audio', 'category' => 'audio', 'priority' => 80],
            ['extension' => 'wav', 'mime_type' => 'audio/wav', 'display_name' => 'WAV Audio', 'category' => 'audio', 'priority' => 79],
            ['extension' => 'ogg', 'mime_type' => 'audio/ogg', 'display_name' => 'OGG Audio', 'category' => 'audio', 'priority' => 78],
            ['extension' => 'm4a', 'mime_type' => 'audio/mp4', 'display_name' => 'M4A Audio', 'category' => 'audio', 'priority' => 77],
            
            // Documents
            ['extension' => 'pdf', 'mime_type' => 'application/pdf', 'display_name' => 'PDF Document', 'category' => 'document', 'priority' => 70],
            ['extension' => 'doc', 'mime_type' => 'application/msword', 'display_name' => 'Word Document', 'category' => 'document', 'priority' => 69],
            ['extension' => 'docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'display_name' => 'Word Document', 'category' => 'document', 'priority' => 68],
            ['extension' => 'xls', 'mime_type' => 'application/vnd.ms-excel', 'display_name' => 'Excel Spreadsheet', 'category' => 'document', 'priority' => 67],
            ['extension' => 'xlsx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'display_name' => 'Excel Spreadsheet', 'category' => 'document', 'priority' => 66],
            ['extension' => 'ppt', 'mime_type' => 'application/vnd.ms-powerpoint', 'display_name' => 'PowerPoint Presentation', 'category' => 'document', 'priority' => 65],
            ['extension' => 'pptx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'display_name' => 'PowerPoint Presentation', 'category' => 'document', 'priority' => 64],
            ['extension' => 'txt', 'mime_type' => 'text/plain', 'display_name' => 'Text File', 'category' => 'document', 'priority' => 63],
            ['extension' => 'csv', 'mime_type' => 'text/csv', 'display_name' => 'CSV File', 'category' => 'document', 'priority' => 62],
            ['extension' => 'rtf', 'mime_type' => 'application/rtf', 'display_name' => 'Rich Text Document', 'category' => 'document', 'priority' => 61],
            
            // Archives
            ['extension' => 'zip', 'mime_type' => 'application/zip', 'display_name' => 'ZIP Archive', 'category' => 'archive', 'priority' => 50],
            ['extension' => 'rar', 'mime_type' => 'application/x-rar-compressed', 'display_name' => 'RAR Archive', 'category' => 'archive', 'priority' => 49],
            ['extension' => '7z', 'mime_type' => 'application/x-7z-compressed', 'display_name' => '7-Zip Archive', 'category' => 'archive', 'priority' => 48],
            ['extension' => 'tar', 'mime_type' => 'application/x-tar', 'display_name' => 'TAR Archive', 'category' => 'archive', 'priority' => 47],
            ['extension' => 'gz', 'mime_type' => 'application/gzip', 'display_name' => 'GZIP Archive', 'category' => 'archive', 'priority' => 46],
            
            // Other
            ['extension' => 'json', 'mime_type' => 'application/json', 'display_name' => 'JSON File', 'category' => 'other', 'priority' => 40],
            ['extension' => 'xml', 'mime_type' => 'application/xml', 'display_name' => 'XML File', 'category' => 'other', 'priority' => 39],
            ['extension' => 'html', 'mime_type' => 'text/html', 'display_name' => 'HTML File', 'category' => 'other', 'priority' => 38],
            ['extension' => 'css', 'mime_type' => 'text/css', 'display_name' => 'CSS File', 'category' => 'other', 'priority' => 37],
            ['extension' => 'js', 'mime_type' => 'application/javascript', 'display_name' => 'JavaScript File', 'category' => 'other', 'priority' => 36],
        ];
        
        foreach ($fileTypes as $fileType) {
            FileTypeSetting::updateOrCreate(
                ['extension' => $fileType['extension']],
                array_merge($fileType, ['is_allowed' => true])
            );
        }
        
        $this->command->info('File type settings seeded successfully.');
    }
}
