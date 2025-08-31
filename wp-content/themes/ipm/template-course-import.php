<?php
/*
Template Name: Course Import Management
*/

// Security check - only allow admins
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();

// Handle CSV upload
if (isset($_POST['upload_courses']) && isset($_FILES['course_csv'])) {
    $result = iipm_process_course_csv_upload($_FILES['course_csv']);
}
?>

<div class="course-import-page" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 140px;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Page Header -->
        <div class="page-header" style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: white; font-size: 2.5rem; margin-bottom: 10px;">Course Import Management</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">
                Bulk import LIA/IOB courses and manage pre-approved course library
            </p>
        </div>

        <!-- Import Results -->
        <?php if (isset($result)): ?>
        <div class="import-results" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 30px;">
            <?php if ($result['success']): ?>
                <div style="color: #059669; background: #ecfdf5; padding: 15px; border-radius: 8px; border-left: 4px solid #059669;">
                    <h3 style="margin: 0 0 10px 0;">✅ Import Successful!</h3>
                    <p>Imported <?php echo $result['imported']; ?> courses successfully.</p>
                    <?php if (!empty($result['skipped'])): ?>
                        <p>Skipped <?php echo count($result['skipped']); ?> duplicate courses.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="color: #dc2626; background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #dc2626;">
                    <h3 style="margin: 0 0 10px 0;">❌ Import Failed</h3>
                    <p><?php echo esc_html($result['error']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="import-content" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            
            <!-- CSV Upload Section -->
            <div class="upload-section" style="margin-bottom: 40px;">
                <h2 style="color: #374151; margin-bottom: 20px;">📊 Bulk Course Import</h2>
                
                <form method="post" enctype="multipart/form-data" style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 2px dashed #d1d5db;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                            Select CSV File:
                        </label>
                        <input type="file" name="course_csv" accept=".csv" required
                               style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                        <small style="color: #6b7280; margin-top: 5px; display: block;">
                            Accepted format: CSV files only. Maximum file size: 10MB
                        </small>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="update_existing" value="1" style="margin-right: 8px;">
                            <span style="color: #374151;">Update existing courses if found</span>
                        </label>
                    </div>
                    
                    <button type="submit" name="upload_courses" 
                            style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px;">
                        📤 Upload and Import Courses
                    </button>
                </form>
                
                <!-- CSV Format Instructions -->
                <div style="margin-top: 20px; padding: 20px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #0ea5e9;">
                    <h4 style="color: #0369a1; margin: 0 0 10px 0;">📋 CSV Format Requirements:</h4>
                    <p style="margin: 0 0 10px 0; color: #374151;">Your CSV file should contain the following columns (in any order):</p>
                    <ul style="margin: 0; color: #6b7280; list-style-type: disc; padding-left: 20px;">
                        <li><strong>title</strong> - Course title (Required)</li>
                        <li><strong>description</strong> - Course description (optional)</li>
                        <li><strong>provider</strong> - LIA, IOB, IIPM, etc. (Required)</li>
                        <li><strong>category</strong> - pensions, savings_investment, ethics, life_assurance (Required)</li>
                        <li><strong>cpd_points</strong> - Number of CPD points (decimal) (Required)</li>
                        <li><strong>course_type</strong> - webinar, workshop, conference, online, self_study</li>
                        <li><strong>duration_minutes</strong> - Duration in minutes (optional)</li>
                        <li><strong>lia_code</strong> - LIA course code (optional)</li>
                        <li><strong>external_url</strong> - Course URL (optional)</li>
                    </ul>
                    <p style="margin: 10px 0 0 0; color: #374151; font-size: 14px;"><strong>📥 Tip:</strong> Download the sample CSV template above to get the correct format!</p>
                </div>
            </div>

            <!-- Download Sample CSV -->
            <div style="margin-bottom: 40px; text-align: center;">
                <a href="<?php echo admin_url('admin-ajax.php'); ?>?action=iimp_download_sample_csv&nonce=<?php echo wp_create_nonce('iipm_download_csv'); ?>" 
                   style="display: inline-block; background: #059669; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-right: 10px;">
                    📥 Download Sample CSV Template
                </a>
                
                <button onclick="testCSVFormat()" 
                        style="background: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    🔍 Test CSV Format
                </button>
            </div>

            <!-- Current Course Statistics -->
            <div class="course-stats" style="margin-bottom: 40px;">
                <h3 style="color: #374151; margin-bottom: 20px;">📈 Current Course Library</h3>
                
                <?php
                global $wpdb;
                $stats = $wpdb->get_results("
                    SELECT 
                        provider,
                        COUNT(*) as course_count,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                    FROM {$wpdb->prefix}test_iipm_cpd_courses 
                    GROUP BY provider 
                    ORDER BY course_count DESC
                ");
                ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <?php foreach ($stats as $stat): ?>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb;">
                        <h4 style="margin: 0 0 10px 0; color: #667eea; font-size: 1.2rem;"><?php echo esc_html($stat->provider); ?></h4>
                        <p style="margin: 0; color: #374151;">
                            <strong><?php echo $stat->active_count; ?></strong> active courses<br>
                            <span style="color: #6b7280;">Total: <?php echo $stat->course_count; ?> courses</span>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <a href="<?php echo home_url('/cpd-admin/'); ?>" 
                   style="display: block; background: #667eea; color: white; padding: 20px; border-radius: 12px; text-decoration: none; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">⚙️</div>
                    <strong>Manage Courses</strong><br>
                    <small>Edit and approve courses</small>
                </a>
                
                <a href="<?php echo home_url('/cpd-reports/'); ?>" 
                   style="display: block; background: #059669; color: white; padding: 20px; border-radius: 12px; text-decoration: none; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">📊</div>
                    <strong>View Reports</strong><br>
                    <small>CPD compliance reports</small>
                </a>
                
                <button onclick="generateSampleCSV()" 
                        style="background: #f59e0b; color: white; padding: 20px; border: none; border-radius: 12px; cursor: pointer; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">📝</div>
                    <strong>Generate Sample</strong><br>
                    <small>Create sample CSV data</small>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function generateSampleCSV() {
    // Create sample CSV content
    const csvContent = `title,description,provider,category,cpd_points,course_type,duration_minutes,lia_code,external_url
"Pension Administration Fundamentals","Basic principles of pension scheme administration","LIA","pensions",2.0,"online",120,"LIA001","https://lia.ie/course1"
"Investment Portfolio Management","Advanced investment strategies and portfolio management","IOB","savings_investment",3.0,"workshop",180,"IOB002","https://iob.ie/course2"
"Professional Ethics in Finance","Ethical considerations in financial services","IIPM","ethics",1.5,"webinar",90,"IIPM001","https://iipm.ie/webinar1"
"Life Insurance Underwriting","Modern approaches to life insurance underwriting","LIA","life_assurance",2.5,"conference",150,"LIA003","https://lia.ie/course3"`;

    // Create and download the file
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sample-courses.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    alert('Sample CSV file downloaded! You can use this as a template for your course imports.');
}

function testCSVFormat() {
    // Generate the same CSV content and display it for debugging
    const csvContent = `title,description,provider,category,cpd_points,course_type,duration_minutes,lia_code,external_url
"Pension Administration Fundamentals","Basic principles of pension scheme administration and regulatory compliance","LIA","pensions",2.0,"online",120,"LIA001","https://lia.ie/course1"
"Investment Portfolio Management","Advanced investment strategies and portfolio management techniques","IOB","savings_investment",3.0,"workshop",180,"IOB002","https://iob.ie/course2"
"Professional Ethics in Finance","Ethical considerations and best practices in financial services","IIPM","ethics",1.5,"webinar",90,"IIPM001","https://iipm.ie/webinar1"
"Life Insurance Underwriting","Modern approaches to life insurance underwriting and risk assessment","LIA","life_assurance",2.5,"conference",150,"LIA003","https://lia.ie/course3"`;

    // Display in a modal or alert for debugging
    const popup = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
    popup.document.write(`
        <html>
        <head><title>CSV Format Test</title></head>
        <body style="font-family: monospace; padding: 20px;">
            <h2>Sample CSV Content (for debugging):</h2>
            <p><strong>Headers:</strong></p>
            <pre style="background: #f0f0f0; padding: 10px; border-radius: 4px;">title,description,provider,category,cpd_points,course_type,duration_minutes,lia_code,external_url</pre>
            
            <p><strong>Full CSV Content:</strong></p>
            <textarea style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;">${csvContent}</textarea>
            
            <p><strong>Instructions:</strong></p>
            <ol>
                <li>Copy the content above and save it as a .csv file</li>
                <li>Or download the template using the green button</li>
                <li>Make sure the file starts with exactly: <code>title,description,provider,category,cpd_points,course_type,duration_minutes,lia_code,external_url</code></li>
            </ol>
        </body>
        </html>
    `);
}
</script>

<?php get_footer(); ?> 