<!-- Programatically show events based on post schuedule wordpres
 For events --- using THE EVENTS CALEDNER PLUGIN -->

// Hook into the 'future_to_publish' action
add_action('save_post', 'create_event_on_scheduled_publish', 10, 3);

function create_event_on_scheduled_publish($post_ID, $post, $update) {
    // Check if the post is already being processed to prevent duplicate events
    
    if (get_post_meta($post_ID, 'event_created', true) === true) {
    return;
  }
  
    // Check if the post type is 'post' and status is 'future'
    if ($post->post_status == 'future' && $post->post_type == 'post') { 
        // Get the post ID, title, and content
        $ID = $post->ID;
        $title = get_the_title($ID);
        $content = apply_filters('the_content', $post->post_content);

        // Extract start and end dates from the content using regex
        preg_match_all('/\b\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\b/', $content, $matches);
        
        if (count($matches[0]) >= 1) {
            $start_date = $matches[0][0];
            $end_date = isset($matches[0][1]) ? $matches[0][1] : '';

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
