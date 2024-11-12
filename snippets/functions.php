// To add in functions.php in your theme  

/**
 * adding CFS fields to current page/post. Add to add_to_context() fucntion
*/
if (function_exists("CFS")) {
	if (get_the_ID() != 0) {
		$context['CFS']  = CFS()->get(false);
	}
	$context['homeCFS']  = CFS()->get(false, get_option('page_on_front'));
}


/**
 * adding orphans filter to twig
*/
public function __construct() {
    if (class_exists('iworks_orphan')) {
        $this->orphan = new iworks_orphan();
    }
}
public function add_to_twig($twig) {
    $twig->addFilter(new Twig\TwigFilter('orphans', [ $this, 'remove_orphans' ]));
}
public function remove_orphans($text) {
    return $this->orphan != null ? $this->orphan->replace($text) : $text;
}
