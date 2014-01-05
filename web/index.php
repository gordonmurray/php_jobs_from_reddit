<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new Silex\Application();
$app->register(new Silex\Provider\TwigServiceProvider(), array('twig.path' => __DIR__ . '/views',));

/**
 * Perform a Reddit lookup, looking in forhire, hiring, jobbit and jobs
 *
 * @param $subreddit
 * @param $keywords
 * @param int $dept
 * @return array
 */
function reddit_lookup($subreddit, $keywords, $dept = 0)
{
    $relevant_posts = array();
    $posts = array();
    $after = '';

    /*
     * Get content from reddit and loop a few times as there are 25 results per page
     */
    for ($n = 0; $n <= $dept; $n++) {
        $reddit_content = file_get_contents('http://www.reddit.com/r/' . $subreddit . '/new/.json?after=' . $after);
        $reddit_array = json_decode($reddit_content, true);
        $posts = array_merge($posts, $reddit_array['data']['children']);
        $after = $reddit_array['data']['after'];
    }

    /*
     * filter the posts returned from reddit based on keywords used in the subreddit
     */
    foreach ($posts as $post) {
        $thread = $post['data'];
        $title = $thread['title'];
        $url = $thread['url'];
        $comments_count = $thread['num_comments'];
        $created = date("d/m/Y H:ia", $thread['created_utc'] - 3600);

        if (stristr($title, $keywords[0]) && stristr($title, $keywords[1])) {
            $relevant_posts[] = "[$created] <a href=\"$url\">$title</a> ($comments_count)";
        }
    }

    return $relevant_posts;
}

$app->get(
    '/',
    function () use ($app) {

        $relevant_posts = array();

        $relevant_posts = reddit_lookup('forhire', array('hiring', 'php'), 5);
        $relevant_posts = array_merge($relevant_posts, reddit_lookup('jobs', array('wanted', 'php'), 4));
        $relevant_posts = array_merge($relevant_posts, reddit_lookup('freelance', array('hiring', 'php'), 4));
        $relevant_posts = array_merge($relevant_posts, reddit_lookup('jobbit', array('hiring', 'php'), 4));

        return $app['twig']->render('results.html.twig', array('posts' => $relevant_posts));
    }
);

$app->run();