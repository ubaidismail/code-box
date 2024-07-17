
add_action('save_post', 'create_event_on_scheduled_publish', 10, 3);

function create_event_on_scheduled_publish($post_ID, $post, $update) {
    // Check if the post is already being processed to prevent duplicate events
    
    if (get_post_meta($post_ID, 'event_created', true) === true) {
    return;
  }
  
    // Check if the post type is 'post' and status is 'future'
    if ( $post->post_type == 'post') {  //$post->post_status == 'future' &&
        // Get the post ID, title, and content
        $ID = $post->ID;
        $title = get_the_title($ID);
        $content = apply_filters('the_content', $post->post_content);
        
        

        $start_date = '';
        $end_date = '';
        $dates = extract_dates_from_content($title . ' ' . $content);

        if (count($dates) >= 1) {
            $start_date = $dates[0]['formatted'];
            $end_date = isset($dates[1]) ? $dates[1]['formatted'] : '';
        }
        
        
        if (!empty($start_date)) {
           
            // Retrieve post categories by name
            $post_categories = wp_get_post_categories($ID);
            $event_categories = array();

            foreach ($post_categories as $c) {
                $cat = get_category($c);
                if ($cat) {
                    // Get the category by name
                    $event_cat = get_term_by('name', $cat->name, 'tribe_events_cat');
                    if ($event_cat) {
                        $event_categories[] = $event_cat->term_id;
                    }
                }
            }

            $event_id = tribe_events()
                ->set_args( [
                    
                    'title' => $title,
                    'post_content' => $content,
                    'status' => 'publish',
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'category'    => $event_categories,
                    'organizer' => ['74'],
                    'venue'=> 17971,
                    'show_map' => true,
                    'show_map_link' =>true,
                ] )
                ->create();
               

           
            update_post_meta($post_ID, 'event_created', true); // to avoid duplication
            
        } else {
            
            // Log error or handle the case when dates are not found
            error_log("Start and/or end date not found in post content for post ID: $ID");
        }
    }
}


function extract_dates_from_content($content) {
    $date_patterns = [
        // ISO 8601 Dates
        '/\b\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?(?:Z)?)?\b/',

        // Numeric Dates
        '/\b\d{1,2}\/\d{1,2}\/\d{2,4}\b/',
        '/\b\d{1,2}-\d{1,2}-\d{2,4}\b/',
        '/\b\d{1,2}\.\d{1,2}\.\d{2,4}\b/',

        // Textual Month Dates
        '/\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?|Sun(?:day)?|Mon(?:day)?|Tue(?:sday)?|Wed(?:nesday)?|Thu(?:rsday)?|Fri(?:day)?|Sat(?:urday)?) \d{1,2}(?:st|nd|rd|th)?,? \d{2,4}\b/i',
        '/\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?|Sun(?:day)?|Mon(?:day)?|Tue(?:sday)?|Wed(?:nesday)?|Thu(?:rsday)?|Fri(?:day)?|Sat(?:urday)?) \d{1,2}\b/i',

        // Abbreviated Month Dates
        '/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{1,2}(?:st|nd|rd|th)?,? \d{2,4}\b/i',
        '/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{1,2}\b/i',

        // Day of the Week with Date
        '/\b(?:Sun|Mon|Tue|Wed|Thu|Fri|Sat),? \d{1,2}\/\d{1,2}\b/i',
        '/\b(?:Sun|Mon|Tue|Wed|Thu|Fri|Sat),? (?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{1,2}\b/i',

        // 24-Hour Time
        '/\b\d{2}:\d{2}\b/',
        '/\b\d{2}:\d{2}:\d{2}\b/',

        // 12-Hour Time with AM/PM
        '/\b\d{1,2}:\d{2} ?[APap]\.?[Mm]\.?\b/',
        '/\b\d{1,2}:\d{2}:\d{2} ?[APap]\.?[Mm]\.?\b/',
        '/\b\d{1,2} ?[APap]\.?[Mm]\.?\b/'
    ];

    $matches = [];
    foreach ($date_patterns as $pattern) {
        preg_match_all($pattern, $content, $pattern_matches);
        if (!empty($pattern_matches[0])) {
            $matches = array_merge($matches, $pattern_matches[0]);
        }
    }

    $parsed_dates = [];
    foreach ($matches as $match) {
        try {
            $date = new DateTime($match);
            $parsed_dates[] = [
                'raw' => $match,
                'formatted' => $date->format('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Date parsing error: " . $e->getMessage() . " for date string: $match");
        }
    }

    return $parsed_dates;
}
