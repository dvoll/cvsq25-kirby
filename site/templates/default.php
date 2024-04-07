<?php
/**
 * @var Kirby\Cms\Site $site
 */

?>
<?php kirby()->response()->code(404); // set status code
echo $site->errorPage()->render(); // render 404 if unmatched template
