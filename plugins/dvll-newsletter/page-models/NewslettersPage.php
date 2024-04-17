<?php
namespace dvll\Newsletter\PageModels;

use Kirby\Cms\Page;
use Kirby\Content\Field;

class NewslettersPage extends Page
{
    /**
     * Override the page title to be static
     * to the template name
     */
    public function title(): Field
    {
        return new Field($this, 'title', 'Newsletter');
    }
}
