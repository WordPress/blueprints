<?php

namespace MyPlayground\Utils\Debug;

require "/wordpress/wp-load.php";

function log_content_state($message)
{
    $args = [
        "post_type" => "post",
        "post_status" => "publish",
        "posts_per_page" => -1,
    ];

    $posts = get_posts($args);
    error_log($message);

    if (!empty($posts)) {
        $last_post = $posts[count($posts) - 1];
        error_log(
            count($posts) .
                " posts - Last Post title: " .
                $last_post->post_title
        );
    } else {
        error_log("No posts found");
    }
}
