Welcome to Melontary!
===================


Melontary is a PHP template framework, it only support UNIX/Linux now.

Build
======
1. *sh configure*

    Then the file 'melontary.php' will be created in *php/*.
    We can get framework root and configuration file path in it. And we also can get the interface '*filltemplate()*' to fill a template file.
    That's all.

Example
=======
1. we have an html file *example.html*.

        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="utf-8">
                <title>test</title>
            </head>
            <body>
                <p>
                    <!--@
                    {
                        "var":"a",
                        "values":{
                            "1st":"hi <!--@{"var":"c","values":{"2nd":"world"}}@-->"
                        }
                    }
                    @-->
                    <!--@
                    {
                        "var":"b"
                    }
                    @-->
                </p>
            </body>
        </html>

    The *var* and *values* are the keywords.

    *var* indicates the name of template variable.

    *values* is an array that will be passed to a callback function to build variable's content.
    
    The value in *values* can contain other varibales.
    
    A template variable is presented as a JSON string.
    
2. Now we write a PHP file to fill the variables in template file.

    *vim php/example.php*
    
        <?php
        require_once('melontary.php');

        function foo1(&$fillValues)
        {
            return $fillValues['1st'];
        }
        function foo2(&$values)
        {
            return 'Hello World';
        }
        function foo3(&$values)
        {
            return $values['2nd'];
        }
        $a = new melontary;
        $a->addHook('a', 'foo1');
        $a->addHook('b', 'foo2');
        $a->addHook('c', 'foo3');
        $result = $a->fillTemplate('example');
        echo $result;
        ?>

    We add an callback function to process a specific template variable via calling the *addHook()*.
    
    All these callback functions will be called in the phase of *filltemplate()*.

3. The result.

    *php php/example.php*

        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="utf-8">
                <title>test</title>
            </head>
            <body>
                <p>
                    hi world
                    Hello World
                </p>
            </body>
        </html>
