# Blood Donation Tracker System

## Overview

The Blood Donation Tracker System provides real-time tracking of blood donations from registration through to distribution. Donors can monitor their donation progress, and staff can update donation stages as the process moves forward.

## Features

- **Real-time Progress Tracking**: Visual progress bar showing current donation stage
- **Stage Management**: 7 distinct stages from registration to ready for use
- **Form Integration**: Automatic progression based on completed forms
- **Staff Dashboard**: Administrative interface for managing donations
- **Mobile Optimized**: Responsive design for mobile devices
- **Auto-refresh**: Automatic updates every 5 minutes

## Donation Stages

1. **Registered** (10%) - Medical history completed
2. **Sample Collected** (25%) - Blood sample collected
3. **Medical Screening** (40%) - Physical examination and screening
4. **Testing** (60%) - Laboratory testing and analysis
5. **Testing Complete** (80%) - All tests completed
6. **Processed** (90%) - Blood processed and prepared
7. **Ready for Use** (100%) - Blood ready for distribution

## Files Created/Modified

### New Files
- `api/blood_tracker.php` - Main tracker API
- `api/staff_tracker.php` - Staff management API
- `templates/blood_tracker.php` - Donor tracker page
- `templates/staff_dashboard.php` - Staff dashboard
- `README-TRACKER.md` - This documentation

### Modified Files
- `includes/functions.php` - Added tracker helper functions
- `templates/dashboard.php` - Integrated tracker widget
- `templates/profile.php` - Added current donation status

## Database Schema

The system uses your existing `donations` and `donation_status_history` tables with additional columns:

```sql
-- Additional columns added to donations table
ALTER TABLE donations 
ADD COLUMN medical_history_completed BOOLEAN DEFAULT FALSE,
ADD COLUMN physical_examination_completed BOOLEAN DEFAULT FALSE,
ADD COLUMN screening_completed BOOLEAN DEFAULT FALSE,
ADD COLUMN blood_collection_completed BOOLEAN DEFAULT FALSE;
```

## Usage

### For Donors

1. **Start Donation Process**: Click "Start Donation Process" on the blood donation page
2. **Complete Medical History**: Fill out the medical history form
3. **View Tracker**: Access the tracker from the dashboard or navigation
4. **Monitor Progress**: See real-time updates on donation stages
5. **Complete Forms**: Follow the required forms as they become available

### For Staff

1. **Access Staff Dashboard**: Navigate to `staff_dashboard.php`
2. **View All Donations**: See statistics and recent donations
3. **Update Status**: Click "Update" to change donation stages
4. **Monitor Progress**: Track donation flow through the system

## API Endpoints

### Blood Tracker API (`/api/blood_tracker.php`)

- `GET ?action=get_tracker` - Get current donation tracker data
- `POST ?action=start_donation` - Start new donation process
- `POST ?action=update_stage` - Update donation stage
- `GET ?action=get_status_history` - Get status change history

### Staff Tracker API (`/api/staff_tracker.php`)

- `POST ?action=update_stage` - Update donation status
- `GET ?action=get_all_donations` - Get all donations
- `GET ?action=get_donation_details` - Get specific donation details
- `POST ?action=complete_form` - Mark form as completed

## Integration Points

### Medical History Form
When a donor submits their medical history, the system automatically:
1. Creates a new donation record
2. Sets status to "Registered"
3. Redirects to the tracker page

### Form Completion
As forms are completed, the system automatically:
1. Updates the form completion status
2. Progresses to the next stage
3. Updates the progress bar

### Profile Updates
When screening is completed, the system:
1. Updates the donor's blood type
2. Increments donation count
3. Shows current status on profile page

## Navigation

The tracker is accessible through:
- **Dashboard**: Blood Tracker card showing current status
- **Navigation**: Bottom navigation bar with Tracker icon
- **Profile**: Current donation status widget
- **Direct URL**: `/templates/blood_tracker.php`

## Customization

### Adding New Stages
To add new stages, modify the `$stages` array in `build_tracker_data()` function:

```php
$stages = [
    'New Stage' => [
        'name' => 'New Stage Name',
        'description' => 'Stage description',
        'icon' => '🔧',
        'progress' => 85,
        'next_stage' => 'Next Stage'
    ]
];
```

### Modifying Progress Calculation
Update the progress calculation in the `calculate_progress()` function:

```php
function calculate_progress($current_stage) {
    $stage_progress = [
        'New Stage' => 85,
        // ... other stages
    ];
    
    return $stage_progress[$current_stage] ?? 0;
}
```

### Changing Auto-progression Rules
Modify the progression rules in `auto_progress_stage()` function:

```php
$progression_rules = [
    'medical_history' => 'Sample Collected',
    'new_form' => 'New Stage',
    // ... other rules
];
```

## Security Considerations

- **Authentication Required**: All tracker endpoints require user login
- **Data Validation**: Input validation on all API endpoints
- **Row Level Security**: Supabase RLS policies protect donor data
- **Session Management**: Secure session handling

## Performance Features

- **Auto-refresh**: Automatic page refresh every 5 minutes
- **Caching**: Efficient database queries with proper indexing
- **Responsive Design**: Mobile-optimized interface
- **Real-time Updates**: Immediate status changes visible

## Troubleshooting

### Common Issues

1. **Tracker Not Showing**: Check if user has an active donation
2. **Status Not Updating**: Verify database triggers are working
3. **Forms Not Progressing**: Check form completion logic
4. **API Errors**: Verify authentication and permissions

### Debug Mode

Enable error reporting in development:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## Future Enhancements

- **Push Notifications**: Real-time stage change notifications
- **Email Alerts**: Status update emails to donors
- **Advanced Analytics**: Detailed donation statistics
- **Mobile App**: Native mobile application
- **Integration**: Connect with hospital systems

## Support

For technical support or questions about the tracker system, refer to the main project documentation or contact the development team.

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, Supabase
