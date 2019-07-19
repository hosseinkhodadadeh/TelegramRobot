<?php
final class TextProcessor
{
    private static $_instance;
    const TITLE_MIN_LENGTH = 2;
    const TITLE_MAX_LENGTH = 10;
    const EXCERPT_MIN_LENGTH = 40;
    private $original_text = '';
    private $text = '';
    private $tags = [];


    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance()
    {
        if (null === static::$_instance) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    private function _countWords($string)
    {
        $list = explode(' ', $string);
        $count = 0;
        foreach ($list as $item) {
            if (mb_strlen($item) > 0)
                $count++;
        }
        return $count;
    }

    public function getTitle()
    {

        $titleByNewLine = substr($this->text, 0, strpos($this->text, "\n"));
        $length = $this->_countWords($titleByNewLine);

        if ($length >= static::TITLE_MIN_LENGTH && $length <= static::TITLE_MAX_LENGTH)
            $title = $this->escape($titleByNewLine);
        else
            $title = $this->getWords(static::TITLE_MAX_LENGTH);

        return $this->convertToFarsi($title);
    }

    private function getWords($count)
    {
        $words = preg_split('/[\s,]+/', $this->text);
        $string = [];
        if (count($words) > $count) {
            for ($i = 0; $i < $count; $i++)
                $string[] = $words[$i];
        } else {
            return $this->text;
        }


        return $this->escape(implode(' ', $string));
    }

    private function escape($text)
    {
        return htmlspecialchars($text, ENT_QUOTES);
    }

    public function getExcerpt()
    {
        return $this->getWords(static::EXCERPT_MIN_LENGTH);
    }

    public function getHTML()
    {
        return $this->convertTagToURL();
    }

    public function filterChannelTitle($title)
    {
        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $title);
    }

    private function convertTagToURL()
    {

        $html = $this->escape($this->text);
        /**
         *  Convert all characters to persian just to be sure!
         */
        $html = $this->convertToFarsi($html);
        /**
         * All  tags are in persian characters. No need to convert to to persian.
         */
        $tags = TagCache::getInstance()->getTags();
        $found = [];
        foreach ($tags as $tag => $url) {
            if (mb_strpos($html, $tag) !== false) {
                /**
                 *   make sure that the tag is not part of a bigger word.
                 *
                 */
                if (mb_strlen($tag) <= 4 && !preg_match("#^{$tag}\$|^{$tag}\s+|\s+{$tag}\$|\s+{$tag}\s+#u", $html, $matches)) {
                    continue;
                }
                $tagHTML = '<a href="' . $url . '" >' . $tag . '</a>';
                $id = hash('sha256', $tag);
                $found [$id] = $tagHTML;
                $html = str_replace($tag, $id, $html);
                $this->tags[] = $tag;

                /**
                 * If arabic and persian version are different, use both versions.
                 */
                $aTag = $this->convertToArabic($tag);
                if ($aTag !== $tag) {
                    $this->tags[] = $aTag;
                }
            }
        }
        foreach ($found as $id => $link) {
            $html = str_replace($id, $link, $html);

        }
        return $html;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function isEmptyString()
    {
        return mb_strlen($this->text) < 1;
    }

    public function setText($text)
    {
        $this->original_text = $this->text = $text;
        $this->tags = [];
        $this->sanitizeText();
        return $this;
    }

    public function getText()
    {
        return $this->text;
    }

    private function sanitizeText()
    {
        //$this->text = $this->ConvertToFarsi($this->text);
        $this->findTags();
        $this->removeEmoji();
        $this->removeChannelName();
        $this->removeURL();
        $this->trimText();
        $this->makenumsen();
        $this->convertArabicToPersian();
        $this->RemoveAd();
        $this->RemovePhonenumber();
    }

    private function trimText()
    {
		$lines=explode('\n',$this->text);
		$tempText='';
		foreach($lines as $line){
			if(strlen(trim($line))>0){
				$tempText.=trim($line).' \n ';
			}
		}
        $this->text = substr($tempText,0,strlen($tempText)-4);
    }

    private function findTags()
    {
        if (preg_match_all('/#\w+/u', $this->text, $matches)) {
            foreach ($matches[0] as $match) {
                $replace = str_replace(
                    ['#', '_'],
                    ['', ' '],
                    $match);
                $this->tags[] = $replace;
                $this->text = str_replace($match, $replace, $this->text);
            }
        }
    }
	
	public function findlinks(){
		preg_match('!(http|https)?(\:\/\/)?[a-zA-Z0-9.?&_/]+\/[^ ]+!',$this->original_text,$matches);
		foreach($matches as $key=>$match){
			if(filter_var($match, FILTER_VALIDATE_URL) and strlen($match)>6 and strpos(strtolower($match),"bot")==false and strpos($match,"?")==false and strpos($match,"@")==false and strpos($match,".me/")>1){
				$post_link[$key]=$match;
			}
		}
		
		if(isset($post_link)){
			return $post_link;
		}else{
			return '';
		}
		
		
			
	}
    private function removeURL()
    {
        $this->text = preg_replace('!(http|https)?(\:\/\/)?[a-zA-Z0-9.?&_/]+\/[^ ]+!', '', $this->text);
    }

    private function removeEmoji()
    {
        $this->text = preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $this->text);
    }
	public function removeGeneralStringEmoji($text){
      return preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]/u', '', $text);
}

    private function removeChannelName()
    {
        $this->text = preg_replace('#@[a-zA-Z0-9\_\-]+#', '', $this->text);
    }
private function makenumsen()
{
	$this->text = str_replace('۰','0', $this->text);
	$this->text = str_replace('٠','0', $this->text);
	$this->text = str_replace('١', '1' , $this->text);
	$this->text = str_replace('۱', '1' , $this->text);
	$this->text = str_replace('٢', '2' , $this->text);
	$this->text = str_replace('۲', '2' , $this->text);
	$this->text = str_replace('٣' ,'3', $this->text);
	$this->text = str_replace('۳' ,'3', $this->text);
	$this->text = str_replace('۴' ,'4', $this->text);
	$this->text = str_replace('٤' ,'4', $this->text);
	$this->text = str_replace('۵' ,'5', $this->text);
	$this->text = str_replace('٥' ,'5', $this->text);
	$this->text = str_replace('۶' ,'6', $this->text);
	$this->text = str_replace('٦' ,'6', $this->text);
	$this->text = str_replace('٧' ,'7', $this->text);
	$this->text = str_replace('۷' ,'7', $this->text);
	$this->text = str_replace('٨' ,'8', $this->text);
	$this->text = str_replace('۸' ,'8', $this->text);
	$this->text = str_replace('٩' ,'9', $this->text);
	$this->text = str_replace('۹' ,'9', $this->text);
	return $this->text;
}
public function convertArabicToPersian(){
	$characters = [
            'ك' => 'ک',
            'دِ' => 'د',
            'بِ' => 'ب',
            'زِ' => 'ز',
            'ذِ' => 'ذ',
            'شِ' => 'ش',
            'سِ' => 'س',
            'ى' => 'ی',
            'ي' => 'ی',
            '١' => '۱',
            '٢' => '۲',
            '٣' => '۳',
            '٤' => '۴',
            '٥' => '۵',
            '٦' => '۶',
            '٧' => '۷',
            '٨' => '۸',
            '٩' => '۹',
            '٠' => '۰',
        ];
        $this->text= str_replace(array_keys($characters), array_values($characters),$this->text);
	return $this->text;
}
private function RemovePhonenumber(){
	$mobile_pattern="(09[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9])";
	$this->text= preg_replace($mobile_pattern, '', $this->text);
	$mobile_pattern="(9[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9])";
	$this->text= preg_replace($mobile_pattern, '', $this->text);
	$mobile_pattern="(\+989[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9])";
	$this->text= preg_replace($mobile_pattern, '', $this->text);
	$mobile_pattern="(989[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9])";
	$this->text= preg_replace($mobile_pattern, '', $this->text);
	$phone_pattern="([0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9])";
	$this->text= preg_replace($phone_pattern, '', $this->text);
	
}
private function RemoveAd(){
	$this->text=str_replace("خريد اینترنتی", '', $this->text );
	$this->text=str_replace("پرداخت درب منزل", '', $this->text );
	$this->text=str_replace("خرید سریع", '', $this->text );
	$this->text=str_replace("خریدآسان", '', $this->text );
	$this->text=str_replace("پرداخت درمحل", '', $this->text );
	$this->text=str_replace("خرید", '', $this->text );
	$this->text=str_replace("آسان", '', $this->text );
	$this->text=str_replace("جهت سفارش", '', $this->text );
	$this->text=str_replace("مزون یشیل", '', $this->text );
	$this->text=str_replace(":", '', $this->text );
	$this->text=str_replace(".", ',', $this->text );
	$this->text=str_replace("  ", ' ', $this->text );
	$this->text=str_replace("/", ',', $this->text );
	$this->text=str_replace("،", ',', $this->text );


	$this->text=str_replace("آ یدی سفارش", '', $this->text );
	$this->text=str_replace("مدل خاتون", '', $this->text );
	$this->text=str_replace("کدسفارش", '', $this->text );
	$this->text=str_replace("کد سفارش", '', $this->text );
	$this->text=str_replace("کد", '', $this->text );
	$this->text=str_replace("Sharifimod", '', $this->text );
	$this->text=str_replace("collection mojde", '', $this->text );
	$this->text=str_replace("collection", '', $this->text );
	$this->text=str_replace("با گارانتی تعویض", '', $this->text );
	$this->text=str_replace("گارانتی تعویض", '', $this->text );
	$this->text=str_replace("?", '', $this->text );
	$this->text=str_replace(" کافه لباس باتخفیف ویژه برای شما", '', $this->text );
	$this->text=str_replace("هزینه درب منزل", '', $this->text );
	$this->text=str_replace("پرو و برای تهران", '', $this->text );
	$this->text=str_replace("سلنا", '', $this->text );
	$this->text=str_replace("مخصوص شما شیک پوشان", '', $this->text );
	$this->text=str_replace("ارزانسرای کیف و کفش یاسمن", '', $this->text );
	$this->text=str_replace("پور", '', $this->text );
	$this->text=str_replace("پورسانت", '', $this->text );
	$this->text=str_replace("پرداخت در محل", '', $this->text );
	$this->text=str_replace("ادمین", '', $this->text );
	$this->text=str_replace("کانال مانتو", '', $this->text );
	$this->text=str_replace("کانال مانتو", '', $this->text );
	$this->text=str_replace("فوری", '', $this->text );
	$this->text=str_replace("پست رایگان", '', $this->text );
	$this->text=str_replace("ارسال رایگان", '', $this->text );
	$this->text=str_replace("رایگان", '', $this->text );
	$this->text=str_replace("ارسال فوری", '', $this->text );
	$this->text=str_replace("تهران", '', $this->text );
	$this->text=str_replace("قم", '', $this->text );
	$this->text=str_replace("کرج", '', $this->text );
	$this->text=str_replace("پیج اینستاگرام", '', $this->text );
	$this->text=str_replace("اینستاگرام", '', $this->text );
	$this->text=str_replace("پرداخت", '', $this->text );
	$this->text=str_replace("درب", '', $this->text );
	$this->text=str_replace("کانال تلگرام", '', $this->text );
	$this->text=str_replace("ارسال", '', $this->text );
	$this->text=str_replace("هزینه", '', $this->text );
	$this->text=str_replace("تولید و پخش", '', $this->text );
	$this->text=str_replace("نقش جهان", '', $this->text );
	$this->text=str_replace("اینترنتی", '', $this->text );
	$this->text=str_replace("کانال", '', $this->text );
	$this->text=str_replace("لینک", '', $this->text );
	$this->text=str_replace("ارسال به سراسر ایران", '', $this->text );
	$this->text=str_replace("ارسال به سراسر کشور", '', $this->text );
	$this->text=str_replace("برادران", '', $this->text );
	$this->text=str_replace("سروش", '', $this->text );
	$this->text=str_replace("ایدی", '', $this->text );
	$this->text=str_replace("تک فروشی", '', $this->text );
	$this->text=str_replace("آف سنتر با تخفیفات باورنکردنی", '', $this->text );
	$this->text=str_replace("گارانتی", '', $this->text );
	$this->text=str_replace("ییییی", '', $this->text );
	$this->text=str_replace("آیدی سفارش", '', $this->text );
	$this->text=str_replace("آیدی", '', $this->text );
	$this->text=str_replace("تلگرام", '', $this->text );
	$this->text=str_replace("واتس اپ", '', $this->text );
	$this->text=str_replace("واتس آپ", '', $this->text );
	$this->text=str_replace("پیک", '', $this->text );
	$this->text=str_replace("پالتو مهسان", '', $this->text );
	$this->text=str_replace("Moderoz_mezon", '', $this->text );
	$this->text=str_replace("آنلاین", '', $this->text );
	$this->text=str_replace("مانتوسرا", '', $this->text );
	$this->text=str_replace("مانتو سرا", '', $this->text );
	$this->text=str_replace("همکاری", '', $this->text );
	$this->text=str_replace("همکار", '', $this->text );
	$this->text=str_replace("عمده", '', $this->text );
	$this->text=str_replace("جین", '', $this->text );
	$this->text=str_replace("تک", '', $this->text );
	$this->text=str_replace("تعویض", '', $this->text );
	$this->text=str_replace("مرجوع", '', $this->text );
	$this->text=str_replace("پرو", '', $this->text );
	$this->text=str_replace("نماینده", '', $this->text );
	$this->text=str_replace("همکاری", '', $this->text );
	$this->text=str_replace("همکار", '', $this->text );
	
	
	
	
	
	$this->text=str_replace("  ", ' ', $this->text );
	$this->text=str_replace("  ", ' ', $this->text );
}
public function PassForbiddenWords(){
	$output_text="  ".$this->text;
	$forbidden_words[0]=strpos($output_text,"شروع قیمت");
	$forbidden_words[1]=strpos($output_text,"ارزانسرا");
	$forbidden_words[2]=strpos($output_text,"مخصوص ارزانسرا");
	$forbidden_words[3]=strpos($output_text,"این کانال محشره");
	$forbidden_words[4]=strpos($output_text,"کانالداران");
	$forbidden_words[5]=strpos($output_text,"کانال دار");
	$forbidden_words[6]=strpos($output_text,"حضوری");
	$forbidden_words[7]=strpos($output_text,"باورش میشه");
	$forbidden_words[8]=strpos($output_text,"فک کن");
	$forbidden_words[9]=strpos($output_text,"ورشکستگی");
	$forbidden_words[10]=strpos($output_text,"ور شکستگی");
	$forbidden_words[11]=strpos($output_text,"بزن رو");
	$forbidden_words[12]=strpos($output_text,"اگه");
	$forbidden_words[13]=strpos($output_text,"واردکننده");
	$forbidden_words[14]=strpos($output_text,"وارد کننده");
	$forbidden_words[15]=strpos($output_text,"بخر");
	$forbidden_words[16]=strpos($output_text,"جوین");
	$forbidden_words[17]=strpos($output_text,"بدو بیا");
	$forbidden_words[18]=strpos($output_text,"امتحان کنید");
	$forbidden_words[19]=strpos($output_text,"تجربه کنید");
	$forbidden_words[20]=strpos($output_text,"قیمت ها");
	$forbidden_words[21]=strpos($output_text,"عمده فروشان");
	$forbidden_words[22]=strpos($output_text,"مقایسه کنید");
	$forbidden_words[23]=strpos($output_text,"زلزله");
	$forbidden_words[24]=strpos($output_text,"میخواین");
	$forbidden_words[25]=strpos($output_text,"بيا ببين");
	$forbidden_words[26]=strpos($output_text,"لوازم آرايش");
	$forbidden_words[27]=strpos($output_text,"رژلب");
	$forbidden_words[28]=strpos($output_text,"رژ لب");
	$forbidden_words[29]=strpos($output_text,"ببین");
	$forbidden_words[30]=strpos($output_text,"ارزانسرای");
	$forbidden_words[31]=strpos($output_text,"عمده سرا");
	$forbidden_words[32]=strpos($output_text,"پیش فروش");
	$forbidden_words[33]=strpos($output_text,"تمام نشده");
	$forbidden_words[33]=strpos($output_text,"تموم نشده");
	$forbidden_words[34]=strpos($output_text,"تومانی");
	$forbidden_words[35]=strpos($output_text,"مقایسه");
	$forbidden_words[36]=strpos($output_text,"بمب");
	$forbidden_words[37]=strpos($output_text,"همه");
	$forbidden_words[38]=strpos($output_text,"شوك");
	$forbidden_words[39]=strpos($output_text,"تبليغ");
	$forbidden_words[40]=strpos($output_text,"فرصت");
	$forbidden_words[41]=strpos($output_text,"فروشان");
	$forbidden_words[42]=strpos($output_text,"انواع");
	$forbidden_words[43]=strpos($output_text,"هر چی");
	$forbidden_words[44]=strpos($output_text,"هرچی");
	$forbidden_words[45]=strpos($output_text,"شروع");
	$forbidden_words[46]=strpos($output_text,"بعلت");
	$forbidden_words[47]=strpos($output_text,"به علت");
	$forbidden_words[48]=strpos($output_text,"بزن");
	$forbidden_words[49]=strpos($output_text,"عجله");
	$forbidden_words[50]=strpos($output_text,"واسطه");
	$forbidden_words[51]=strpos($output_text,"بدو");
	$forbidden_words[52]=strpos($output_text,"تومنی");
	$forbidden_words[53]=strpos($output_text,"ارزانسرا");
	$forbidden_words[54]=strpos($output_text,"آکنه");
	$forbidden_words[55]=strpos($output_text,"تست شده");
	$forbidden_words[56]=strpos($output_text,"مگه میشه");
	$forbidden_words[57]=strpos($output_text,"قیمتا");
	$forbidden_words[58]=strpos($output_text,"بیا");
	$forbidden_words[59]=strpos($output_text,"معدن");
	$forbidden_words[60]=strpos($output_text,"تغییر فصل");
	$forbidden_words[61]=strpos($output_text,"رونمایی");
	$forbidden_words[62]=strpos($output_text,"کلیک");
	$forbidden_words[63]=strpos($output_text,"بخونید");
	$forbidden_words[64]=strpos($output_text,"عضو");
	$forbidden_words[65]=strpos($output_text,"زیر قیمت");
	$forbidden_words[66]=strpos($output_text,"کلیک");
	$forbidden_words[67]=strpos($output_text,"کلیک");
	$forbidden_words[68]=strpos($output_text,"اشانتیون");
	$forbidden_words[69]=strpos($output_text,"آرایش");
	$forbidden_words[70]=strpos($output_text,"عمده");

	
	if(array_sum($forbidden_words)>0){
		return false;
	}else{
		return true;
	}

}
public function AddAdminText(){
	if(strlen($this->text)<175){
		$this->text=$this->text."\r\n"."سفارش"."\r\n"."@Elmira_Payande";
	}
	if(strlen($this->text)<155){
		$this->text=$this->text."\r\n"."بوتیک آنلاین رسیده"."\r\n"."@re30deh";
	}
	
}
public function AddInstaAdminText(){
	$this->text=$this->text."\r\n"."سفارش از طریق دایرکت و تلگرام "."\r\n"."@re30deh";
	$this->text=$this->text."\r\n"."کانال تلگرام ما"."\r\n"."@re30deh";
	
	
}
public function HasPrice(){
	$plus=0;
	return $this->UpdatePrices($plus,'constant');
	
}
public function UpdatePrices($plus,$inc_type){
	$patterns[0]="([0-9\,]+ تومن)";
	$patterns[1]="([0-9\,]+ تومان)";
	$patterns[2]="([0-9\,]+ ریال)";

	$patterns[4]="([0-9\,]+تومن)";
	$patterns[5]="([0-9\,]+تومان)";
	$patterns[6]="([0-9\,]+ریال)";
	
	$patterns[8]="(قیمت [0-9\,]+)";
	
	$patterns[9]="('فروش[0-9\,]+)";
	$patterns[10]="(فروش [0-9\,]+)";
	$patterns[11]="([0-9\,]+ قیمت)";
	$patterns[12]="(قیمت[0-9\,]+)";
	$patterns[13]="(قیمت [0-9\,]+)";
	$patterns[14]="([0-9\,]+ هزار تومان)";
	$patterns[15]="([0-9\,]+ هزار تومن)";
	//$patterns[16]="([0-9\,]+)";
	//$patterns[17]="([0-9\,]+)";
	//$patterns[18]="([0-9\,]+)";
	$counter=1000;
	//checking all price patterns one by one
	foreach($patterns as $num1=> $pattern){
		preg_match($pattern , $this->text, $matches);
		if(isset($matches)){
			foreach($matches as $num2=> $match){
				$counter=$counter+1;
				$this->text=str_replace($match, "GTGTGTGTGTGTGTGTGTGTGTG".$counter."GTGTGT", $this->text );
				$price_phrase=str_replace(",", '', $match);
				$price_phrase=str_replace("000", '', $price_phrase);
				$removable_phrase = preg_replace('/[0-9]+/', '', $price_phrase);
				$price=str_replace($removable_phrase, '', $price_phrase);
				if(intval($price)>1000){//price is in toman
					if($inc_type=='percent'){
						$price=intval($price)* $plus . "تومان";
					}else{
						$price=intval($price)+($plus*1000) . "تومان";
					}
				}else{//price is in hezar toman
					if($inc_type=='percent'){
						$price=intval($price)*$plus . " هزار تومان";
					}else{
						$price=intval($price)+$plus . " هزار تومان";
					}
				}
				
				$prices[$counter]=$price;
			}
		}
	}
	if(isset($prices)){
		foreach($prices as $num=> $price){
			$this->text=str_replace('GTGTGTGTGTGTGTGTGTGTGTG'.$num."GTGTGT", $price, $this->text );
		}
		$this->text=str_replace("تومانت", 'تومان', $this->text );
		$this->text=str_replace("هزار تومانهزار تومان", 'هزار تومان', $this->text );
		$this->text=str_replace("هزار تومان هزار تومان", 'هزار تومان', $this->text );
		return true;
	}else{
		return false;
	}
	
}


public function PriceAd(){
	$patterns[0]="(از[0-9\,]+تومن)";
	$patterns[1]="(از [0-9\,]+تومن)";
	$patterns[2]="(از[0-9\,]+ تومن)";
	$patterns[3]="(از [0-9\,]+ تومن)";
	$patterns[4]="(از[0-9\,]+تومان)";
	$patterns[5]="(از [0-9\,]+تومان)";
	$patterns[6]="(از [0-9\,]+ تومان)";
	$patterns[7]="(از[0-9\,]+ تومان)";
	
	$patterns[8]="(تا[0-9\,]+تومن)";
	$patterns[9]="(تا [0-9\,]+تومن)";
	$patterns[10]="(تا[0-9\,]+ تومن)";
	$patterns[11]="(تا [0-9\,]+ تومن)";
	$patterns[12]="(تا[0-9\,]+تومان)";
	$patterns[13]="(تا [0-9\,]+تومان)";
	$patterns[14]="(تا [0-9\,]+ تومان)";
	$patterns[15]="(تا[0-9\,]+ تومان)";
	
	$patterns[16]="(از[0-9\,]+هزار)";
	$patterns[17]="(از[0-9\,]+ هزار)";
	$patterns[18]="(از [0-9\,]+ هزار)";
	$patterns[19]="(از [0-9\,]+هزار)";
	$patterns[20]="(تا[0-9\,]+هزار)";
	$patterns[21]="(تا[0-9\,]+ هزار)";
	$patterns[22]="(تا [0-9\,]+ هزار)";
	$patterns[23]="(تا [0-9\,]+هزار)";
	
	//$patterns[0]="(ghabl[0-9\,]+bad)";
	//$patterns[0]="(ghabl[0-9\,]+bad)";
	//$patterns[0]="(ghabl[0-9\,]+bad)";
	//$patterns[0]="(ghabl[0-9\,]+bad)";
	//$patterns[0]="(ghabl[0-9\,]+bad)";
	//$patterns[0]="(ghabl[0-9\,]+bad)";


	$counter=1000;
	//checking all price patterns one by one
	foreach($patterns as $num1=> $pattern){
		preg_match($pattern , $this->text, $matches);
		if(isset($matches)){
			foreach($matches as $num2=> $match){
				$counter=$counter+1;
				$this->text=str_replace($match, "GTGTGTGTGTGTGTGTGTGTGTG".$counter."GTGTGT", $this->text );
				$price_phrase=str_replace(",", '', $match);
				$price_phrase=str_replace("000", '', $price_phrase);
				$removable_phrase = preg_replace('/[0-9]+/', '', $price_phrase);
				$price=str_replace($removable_phrase, '', $price_phrase);
				
				$plus=1;
				$price=intval($price)* $plus . "تومان";
				
					
				
				
				
				$prices[$counter]=$price;
			}
		}
	}
	if(isset($prices)){
		foreach($prices as $num=> $price){
			//$this->text=str_replace('GTGTGTGTGTGTGTGTGTGTGTG'.$num."GTGTGT", $price, $this->text );
		}

		return true;
	}else{
		return false;
	}
	
}
public function getProductType(){
	$localText=$this->text;
	$localText=str_replace('کیفیت','wwwwwww',$localText);
	$localText=str_replace('کیفیت','wwwwwww',$localText);
	if(mb_strpos($localText,'سارافون')>0){
		return 'saraphon';
	}elseif(mb_strpos($localText,'سیوشرت')>0){
		return 'sueeshirt';
	}elseif(mb_strpos($localText,'سارافن')>0){
		return 'saraphon';
	}elseif(mb_strpos($localText,'روسری')>0){
		return 'roosari';
	}elseif(mb_strpos($localText,'پیراهن')>0){
		return 'pirahan';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'مانتو')>0){
		return 'manto';
	}elseif(mb_strpos($localText,'کفش')>0){
		return 'kafsh';
	}elseif(mb_strpos($localText,'کت و شلوار')>0){
		return 'kotshalvar';
	}elseif(mb_strpos($localText,'دامن')>0){
		return 'daman';
	}elseif(mb_strpos($localText,'شرت')>0){
		return 'short';
	}elseif(mb_strpos($localText,'مجلسی')>0){
		return 'majlesi';
	}elseif(mb_strpos($localText,'کتونی')>0){
		return 'katooni';
	}elseif(mb_strpos($localText,'شومیز')>0){
		return 'shoomiz';
	}elseif(mb_strpos($localText,'سرهمی')>0){
		return 'sarehami';
	}elseif(mb_strpos($localText,'تاپ')>0){
		return 'top';
	}elseif(mb_strpos($localText,'کیف')>0){
		return 'kif';
	}elseif(mb_strpos($localText,'شال')>0){
		return 'shal';
	}elseif(mb_strpos($localText,'بلوز')>0){
		return 'bolooz';
	}elseif(mb_strpos($localText,'پیراهن')>0){
		return 'pirahan';
	}elseif(mb_strpos($localText,'صندل')>0){
		return 'sandal';
	}elseif(mb_strpos($localText,'روسری')>0){
		return 'roosari';
	}elseif(mb_strpos($localText,'تونیک')>0){
		return 'toonik';
	}elseif(mb_strpos($localText,'پاشنه')>0){
		return 'kif';
	}elseif(mb_strpos($localText,'شلوار')>0){
		return 'shalvar';
	}elseif(mb_strpos($localText,'نيم تنه')>0){
		return 'nim_tane';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'qq')>0){
		return 'qq';
	}elseif(mb_strpos($localText,'سوتین')>0){
		return 'sutian';
	}elseif(mb_strpos($localText,'تاپ')>0){
		return 'top';
	}elseif(mb_strpos($localText,' ست ')>0){
		return 'set';
	}else{
		return 'Unknown';
	}
	
}
    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    Public function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    protected function __wakeup()
    {
    }
}