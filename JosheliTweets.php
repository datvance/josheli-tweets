<?php

require __DIR__ . '/vendor/autoload.php';

class JosheliTweets
{
  public function __construct()
  {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
  }

  public function tweet()
  {
    $status = $this->formatTweetFromWordpressPost($this->getRandomWordpressPost());

    if($status)
    {
      $settings = [
        'oauth_access_token' => getenv('TWITTER_OAUTH_ACCESS_TOKEN'),
        'oauth_access_token_secret' => getenv('TWITTER_OAUTH_ACCESS_TOKEN_SECRET'),
        'consumer_key' => getenv('TWITTER_CONSUMER_KEY'),
        'consumer_secret' => getenv('TWITTER_CONSUMER_SECRET')
      ];

      $url = 'https://api.twitter.com/1.1/statuses/update.json';
      $request_method = 'POST';

      $twitter = new TwitterAPIExchange($settings);
      $twitter->buildOauth($url, $request_method)
        ->setPostfields(['status' => $status])
        ->performRequest();
    }
  }

  protected function getRandomWordpressPost()
  {
    $mysqli = new mysqli(
      getenv('WP_DB_HOST'),
      getenv('WP_DB_USER'),
      getenv('WP_DB_PASS'),
      getenv('WP_DB_NAME'),
      getenv('WP_DB_PORT') ? getenv('WP_DB_PORT') : 3306
    );

    return $mysqli
      ->query('SELECT * FROM `wp_posts` WHERE `post_type` = "post" AND `post_status` = "publish" ORDER BY rand() LIMIT 1')
      ->fetch_object();
  }

  protected function formatTweetFromWordpressPost($post)
  {
    $post_date = date('M j, Y', strtotime($post->post_date));
    list($post_year, $post_month, $post_day) = explode('-', strtok($post->post_date, ' '));
    $permalink = "https://josheli.com/knob/{$post_year}/{$post_month}/{$post_day}/{$post->post_name}";
    $status = "{$post_date}: {$post->post_title} - {$permalink}";

    $post_content = preg_replace(['@\[.*?\]@', '/\s\s+/'], ' ', strip_tags($post->post_content));
    $status .= ' - ' . $post_content;
    $status = trim(substr($status, 0, 277)) . '...';

    return $status;
  }
}