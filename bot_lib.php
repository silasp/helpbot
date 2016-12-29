<?php

class db {
    const host = 'localhost';
    const user = 'root';
    const pass = 'root123';
    const dbname = 'learningbot';
    
    private $_pdo;
    
    public function __construct() {        
        $dsn = 'mysql:host=' . self::host . ';dbname=' . self::dbname;        
        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ); 

        $this->_pdo = new PDO($dsn, self::user, self::pass, $options);
    }
    
    public function getPdo() {
        return $this->_pdo;
    }
    
}


class learningBot {
    
    private $_db;
    private $_phrase;
    private $_phrase_id;
    private $_hash;
    private $_hash_id;
    private $_alt_id;
    private $_bot_alt_id;
    private $_phraseSaved;
    
    public function db() {
                
        if (!isset($this->_db)) {
            $db = new db();                        
            $this->_db = $db->getPdo();
        }
        return $this->_db;
    }
    
    public function query($sql) {
        return $this->db()->query($sql);        
    }
    
    private function _dropTables() {
        $sql = "DROP TABLE phrases;
                DROP TABLE hashes;
                DROP TABLE responses;
        ";
        
        $this->query($sql);
    }
    
    private function _createTables() {
        $sql = "CREATE TABLE phrases (
                phrase_id int unsigned NOT NULL AUTO_INCREMENT,
                phrase varchar(255) NOT NULL,
                hash_id int unsigned NOT NULL,
                UNIQUE phrase (phrase),
                PRIMARY KEY (phrase_id)
                );
                CREATE TABLE hashes (
                hash_id int unsigned NOT NULL AUTO_INCREMENT,
                hash varchar(255) NOT NULL,
                alt_id int unsigned NOT NULL,
                PRIMARY KEY (hash_id),
                UNIQUE hash (hash),
                INDEX alt_id (alt_id)
                );
                CREATE TABLE responses (
                call_alt_id int unsigned NOT NULL,
                response_alt_id int unsigned NOT NULL,
                CONSTRAINT pk_call_response PRIMARY KEY (call_alt_id,response_alt_id)
                );
        ";
        
        $this->query($sql);
    }
    
    private function _addInitialData() {
        $this->_alt_id = 1;
        $fillers = [
            'Uh huh.',
            'Right.',
            'Sure thing.',
            'Ok.',
            'Go on.',
            'Got that.',
            'I understand.',
            'What else?',
            'Yep.',
            'Sure.',
            'Righto.',
            'That\'s interesting.',
            'Wow! Really?',
            'Huh?',
            'Anything else?',
            'Hmm. Fascinating.',
            'How can that be?',
            'Yeah, ok.',
            'May I ask why that is?',
            'I\'d love to understand more about that.',
            'That\'s hard to believe.',
            'I\'m sorry to hear that',
            'Can you explain what you mean by that?',
            'Please explain.',
            'What do you think?'
        ];
        
        $this->_addPhrases($fillers);
        
        $pre_responses = [
            'I think I know what you need to do.',
            'Ok, I have a solution for you...',
            'Right. Let\'s try this...',
            'Here\'s something you could try...',
            'Mmm. There\'s only one thing left to do...',
            'My friend, you have to do this...',
            'Based on what you have told me, here\'s what I think you should do...',
            'The most helpful advice I have for you is this...',
            'I would suggest this...',
            'Thanks for explaining the situation. Here\'s what I want you to do...'
        ];
        
        $this->_addPhrases($pre_responses);

        $responses = [
            'Please read the manual.',
            'Have you considered turning it off and on again?',
            'Try logging on again, without caps-lock on.',
            'Can you log that in Zendesk? We will look at it shortly.',
            'Just use your brain. With a bit of thought, you\'ll be able to figure this one out on your own!',
            'Go to google.com and type in what you just told me. Then do what it says.',
            'Unplug everything, then plug it in again, and make sure all the lights are on.',
            'Just sit tight. We\'re sorting that out as we speak',
        ];
        
        $this->_addPhrases($responses);
        
        
        
    }
    
    private function _addPhrases($phrases) {
        $this->_alt_id++;
        
        foreach ($phrases as $phrase) {
            $this->_addCatchallPhrase($phrase);
        }
    }
        
    private function _addCatchallPhrase($phrase) {
        $this->setPhrase($phrase);
        $this->_addPhraseToDatabase();
    }
    
    private function _tablesNotThere() {
        $sql = "SHOW TABLES";
        $result = $this->query($sql);        
        return (count($result) == 0);             
    }
    
    public function __construct() {
        // Testing only.
        // $this->_dropTables();
        
        if ($this->_tablesNotThere()) {
            $this->_createTables();
            $this->_addInitialData();
        }
    }
    
    private function _punctuate($phrase) {
        $phrase = ucfirst(preg_replace('!\s+!', ' ', trim($phrase)));
        $last_char = substr($phrase, -1);
        if ($last_char != '!' && $last_char != '.' && $last_char != '?') {
            $phrase .= '.';
        }
        
        return $phrase;
    }
    
    private function _hash($phrase) {
        // Some phrase with tons of "stuff" in it -> somephrasewithtonsofstuffinit
        return preg_replace("/[^a-z]/", '', strtolower($phrase));
    }
    
    private function _checkForDbError($sth) {
        if (!$sth) {
            echo "\nPDO::errorInfo():\n";
            print_r($this->db()->errorInfo()); 
        }
    }
    
    private function _addHashToDatabase() {
        $this->_getAltIdForHash();        
        $sql = "INSERT IGNORE INTO hashes (hash, alt_id)
                VALUES (?,?);";
        $binds = [
            $this->_hash,
            $this->_alt_id
        ];
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        $sth->execute($binds);        
    }
    
    private function _getHashId() {
        $sql = "SELECT hash_id FROM hashes 
                WHERE hash = ? LIMIT 1";
        $binds = [ $this->_hash ];
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        $sth->execute($binds);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $this->_hash_id = (int)$result['hash_id'];        
    }
    
    private function _insertPhrase() {
        $sql = "INSERT IGNORE INTO phrases (phrase, hash_id)
                VALUES (?,?);";
        $binds = [
            $this->_phrase,
            $this->_hash_id
        ];
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        $sth->execute($binds);        
        $this->_phraseSaved = true;
    }
    
    private function _addToResponses() {
        if (!$this->_bot_alt_id) {
            return;
        }
                       
        $sql = "INSERT IGNORE INTO responses (call_alt_id, response_alt_id)
                VALUES (?,?);";
        $binds = [
            $this->_bot_alt_id,
            $this->_alt_id
        ];
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        $sth->execute($binds);        
    }
    
    private function _addPhraseToDatabase() {        
        $this->_addHashToDatabase();
        $this->_getHashId();
        $this->_insertPhrase();
        $this->_addToResponses();        
    }
    
    public function setPhrase($phrase) {
        $this->_phrase = $this->_punctuate($phrase);
        $this->_hash = $this->_hash($phrase);
        $this->_phraseSaved = false;        
        return $this;
    }
    
    private function _getAltIdForResponse() {
        if (!$this->_alt_id) {
            return;
        }
        $this->_getAltIdFromResponses($this->_alt_id);
    }
    
    private function _getAltIdForHash() {
        
        if (!$this->_alt_id) {
        // get from responses
            $this->_getAltIdFromResponses($this->_bot_alt_id);
        }
        
        if (!$this->_alt_id) {
            // get from hash
            $this->_getAltIdFromHash();
        }
        
        if (!$this->_alt_id) {
            // get new unique alt id from hash
            $this->_getNewAltId();
        }
        
        if (!$this->_alt_id) {
            $this->_alt_id = 2;
        }
    }
    
    private function _getNewAltId() {
        $sql = "SELECT MAX(alt_id) + 1 AS max_alt_id FROM hashes";        
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $this->_alt_id = (int)$result['max_alt_id']; 
    }
    
    private function _getAltIdFromResponses( $call_alt_id ) {
        $sql = "SELECT response_alt_id FROM responses 
                WHERE call_alt_id = ? ORDER BY RAND() LIMIT 1";
        $binds = [ $call_alt_id ];
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        $sth->execute($binds);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $this->_alt_id = (int)$result['response_alt_id'];         
    }
    
    private function _getAltIdFromHash() {    
        $sql = "SELECT alt_id FROM hashes 
                WHERE hash = ? LIMIT 1";
        $binds = [ $this->_hash ];
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        $sth->execute($binds);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $this->_alt_id = (int)$result['alt_id'];        
    }
    
    private function _phraseKnown() {
        if (!$this->_phraseSaved) {
            $this->_addPhraseToDatabase();
        }  
        $this->_getAltIdForResponse();        
        
        return $this->_alt_id;
    }
    
    private function _getKnownResponse() {
        
        
        
        $sql = 'SELECT p.phrase, h.alt_id, p.phrase_id FROM hashes h 
                LEFT JOIN phrases p USING (hash_id) 
                WHERE p.phrase_id != ?
                AND h.alt_id = ? 
                ORDER BY RAND() 
                LIMIT 1;';
        
        return $this->_getResponse($sql);
    }
    
    private function _getRandomResponse() {
        $sql = 'SELECT p.phrase, h.alt_id, p.phrase_id FROM hashes h                             
                LEFT JOIN phrases p USING (hash_id)
                WHERE p.phrase_id != ?
                ORDER BY RAND() 
                LIMIT 1;';
        
        return $this->_getResponse($sql);
    }
    
    private function _getResponse($sql) {
        $sth = $this->db()->prepare($sql);
        $this->_checkForDbError($sth);
        if (isset($this->_alt_id)) {
            $binds = [$this->_phrase_id, $this->_alt_id];
        }
        else {
            $binds = [$this->_phrase_id];
        }
        $sth->execute($binds); 
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        
        if (empty($result)) {
            unset ($this->_alt_id);
            return $this->_getRandomResponse();
        }
            
        $this->_phrase_id = $result['phrase_id'];
        $this->_alt_id = $result['alt_id'];        
        return $result['phrase'];
    }
    
    
    public function getResponse() {
        if ($this->_bot_alt_id == -1) {
            return "Welcome to IT Helpdesk! Can I help you?";
        }
        if ($this->_phraseKnown()) {
            return $this->_getKnownResponse();
        }
        
        return $this->_getRandomResponse();        
    }
    
    public function loadFormData() {        
        $phrase = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING);
        if ($phrase) {
            $this->setPhrase($phrase);
            $this->_bot_alt_id = filter_input(INPUT_POST, 'bot_alt_id', FILTER_SANITIZE_NUMBER_INT);
            $this->_phrase_id = filter_input(INPUT_POST, 'phrase_id', FILTER_SANITIZE_NUMBER_INT);
        }
        else {
            $this->_bot_alt_id = -1;
            $this->_phrase_id = -1;
        }
        return $this;
    }
    
    public function getAltId() {
        return $this->_alt_id;
    }
    
    public function getPhraseId() {
        return $this->_phrase_id;
    }
    
}

function bot() {

    $greeting = 'Welcome to IT helpdesk, can I help you?';

    $fillers = [
        'Uh huh.',
        'Right.',
        'Sure thing.',
        'Ok.',
        'Go on.',
        'Got that.',
        'I understand.',
        'What else?',
        'Yep.',
        'Sure.',
        'Righto.',
        'That\'s interesting.',
        'Wow! Really?',
        'Huh?',
        'Anything else?',
        'Hmm. Fascinating.',
        'How can that be?',
        'Yeah, ok.',
        'May I ask why that is?',
        'I\'d love to understand more about that.',
        'That\'s hard to believe.',
        'I\'m sorry to hear that',
        'Can you explain what you mean by that?',
        'Please explain.',
        'What do you think?'
    ];

    $pre_responses = [
        'I think I know what you need to do.',
        'Ok, I have a solution for you...',
        'Right. Let\'s try this...',
        'Here\'s something you could try...',
        'Mmm. There\'s only one thing left to do...',
        'My friend, you have to do this...',
        'Based on what you have told me, here\'s what I think you should do...',
        'The most helpful advice I have for you is this...',
        'I would suggest this...',
        'Thanks for explaining the situation. Here\'s what I want you to do...'
    ];

    $responses = [
        'Please read the manual.',
        'Have you considered turning it off and on again?',
        'Try logging on again, without caps-lock on.',
        'Can you log that in Zendesk? We will look at it shortly.',
        'Just use your brain. With a bit of thought, you\'ll be able to figure this one out on your own!',
        'Go to google.com and type in what you just told me. Then do what it says.',
        'Unplug everything, then plug it in again, and make sure all the lights are on.',
        'Just sit tight. We\'re sorting that out as we speak',
    ];


    $farewell = 'Thanks for chatting with IT helpdesk! Is there something else I can help with?';

    $cr = "\r\n";

    // *********************************************88

    $step = intval($_POST['step']);

    if ($step == 0 || intval($_POST['reset']) == '1') {
        return [
            'response' => $greeting,
            'step' => 1
        ];
    }

    $total = intval($_POST['total']);

    if ($total == 0) {
        $total = rand(2, 5);
    }

    if ($step < $total) {
        $step++;
        
        $last_filler_id = intval($_POST['filler_id']);
        do  {
            $filler_id = rand(0, count($fillers) - 1);
        } while ($filler_id == $last_filler_id);

        return [
            'response' => $fillers[$filler_id],
            'step' => $step,
            'total' => $total
        ];
    }

    $punchline = $pre_responses[rand(0, count($pre_responses) - 1)] . $cr .
            $responses[rand(0, count($responses) - 1)] . $cr .
            $farewell;

    return [
        'response' => $punchline,
        'step' => 1
    ];
}

function randomCat() {
    $cat = json_decode(file_get_contents('http://random.cat/meow'));
    $cat_image = '';
    if (isset($cat->file)) {
        $cat_heading = '<h5>Just for fun, here\'s a random cat.</h5>';
        $cat_image = '<img src="' . htmlentities($cat->file) . '" class="img-fluid img-thumbnail" alt="Random Cat" />';
    }
    return $cat_heading . $cat_image;
}

function randomMeme() {
    $meme_url = 'http://api.giphy.com/v1/gifs/search?q=meme&api_key=dc6zaTOxFJmzC&limit=1&offset=' . rand(0, 4995);
    $meme = json_decode(file_get_contents($meme_url));
    $meme_image = '';
    if (isset($meme->data[0]->images->original->url)) {
        $meme_heading = '<h5>And, for some more fun, here\'s a random meme.</h5>';
        $meme_image = '<img src="' . htmlentities($meme->data[0]->images->original->url) . '" class="img-fluid img-thumbnail" alt="Random Meme" />';
    } else {
        print_r($meme);
    }
    return $meme_heading . $meme_image;
}


