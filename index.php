<?php
include('bot_lib.php');

$lb = new learningBot();
$lb->loadFormData();

// $bot = bot();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="Silas Palmer">
        <meta name="description" content="IT Helpdesk Live Chat">
        <title>IT Helpdesk Live Chat 2.0 - with bonus cats and memes.</title>
        <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha256-/SIrNqv8h6QGKDuNoLGA4iret+kyesCkHGzVUUV0shc=" crossorigin="anonymous"></script>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>         
    </head>
    <body>     
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-md-8 col-md-offset-2">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h2>Live Helpdesk Support - Chat Now!</h2>
                        </div>
                        <div class="panel-content">
                            <form method="POST">
                                <div class="form-group">                            
                                    <textarea class="form-control" id="chat" rows="6"><?php echo $lb->getResponse(); // $bot['response']; ?></textarea>              
                                </div>
                                <div class="form-group">            
                                    <input type="text" class="form-control" id="message" name="response" placeholder="Type your message here">            
                                </div> 
                                <input type="hidden" name="step" value="<?php // echo $bot['step']; ?>">
                                <input type="hidden" name="phrase_id" value="<?php echo $lb->getPhraseId(); ?>">
                                <input type="hidden" name="bot_alt_id" value="<?php echo $lb->getAltId(); ?>">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <button type="submit" class="btn btn-primary" name="reset" value="1">Start Over</button>
                                <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#treats">Don't click this</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div id="treats" class="collapse">                
                <div class="row">
                    <div class="panel-primary">
                        <div class="panel-content">
                            <div class="col-sm-4 col-md-offset-2">
                                <?php // echo randomCat(); ?>                    
                            </div>
                            <div class="col-sm-4">                    
                                <?php // echo randomMeme(); ?>
                            </div>
                        </div>
                    </div>
                </div>    
            </div>
        </div>
    </body>
</html>
