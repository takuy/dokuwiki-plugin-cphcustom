<?php
 
if(!defined('DOKU_INC')) die();

if(class_exists("helper_plugin_redirect", TRUE)) {

    class action_plugin_cphcustom extends DokuWiki_Action_Plugin { 
    
        /**
         * Register its handlers with the DokuWiki's event controller
         */
        public function register(Doku_Event_Handler $controller) {

            $events = array('TPL_CONTENT_DISPLAY');
    
            foreach($events as $evt) {
            $controller->register_hook($evt, 'BEFORE', $this, 'fix_redirect_links');
            }
        }

        /**
         * @param Doku_Event $event  event object by reference
         * @param mixed      $param  empty
         * @param string     $advise the advise the hook receives
         */
        public function fix_redirect_links (&$event, $param=null) {     
            libxml_use_internal_errors(TRUE);
            $DOM = DOMDocument::loadHTML($event->data);
            $aTags = $DOM->getElementsByTagName("a");
            $nb = $aTags->length;
            for($pos=0; $pos<$nb; $pos++) {
                $node = $aTags[$pos];
                $classes = $node->getAttribute("class");
                if(preg_match('/wikilink2/i', $classes)){
                    $title = $node->getAttribute("title");

                    $redirHelper = new helper_plugin_redirect();
                    $redirects = $redirHelper->getRedirectURL($title);


                    if($redirects) {
                        $url = parse_url($redirects);
                        $id = "";
                        if($url["query"]) {
                            $query = parse_str($url["query"]);
                            if($query["id"]) $id = $query["id"];
                        }
                        if($url["path"]) {
                            $path = $url["path"];
                            $dokuRel =  trim(DOKU_REL, "/");
                            $cleanPath = ltrim(preg_replace(["/${dokuRel}/i", '/\/doku.php/i'], ["", ""], $path), "/");
                            if($cleanPath) {
                                $cleanPath = str_replace("/", ":", $cleanPath);
                                $id = $cleanPath;
                            }
                        }
                        if(page_exists($id)) {
                            $newClasses = preg_replace('/wikilink2/i', "wikilink", $classes);
                            $node->removeAttribute('class');
                            $node->setAttribute("class", $newClasses);
                        }

                    }
                }
            }
            $event->data = $DOM->saveHTML();
        }
    }
}