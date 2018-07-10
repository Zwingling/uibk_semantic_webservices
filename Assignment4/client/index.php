<html>
<head>
    <title>Hydra Api Client</title>
    <script type="text/javascript">
        function updateURL(url){
            var elems = document.getElementsByName("url");
            for(var i=0; i<elems.length;i++)
            {
                if(url.indexOf("http") == -1)
                    elems[i].value="http://localhost:5000"+url;
                else
                    elems[i].value=url;

            }
        }
    </script>
</head>
<body>


<?php
/**
 *
 * Created by PhpStorm.
 * User: ivan
 * Date: 20.06.18
 * Time: 20:33
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include_once('http.php'); //where HttpRequest.php is the saved file
if(isset($_SESSION["url"]))
    $base_url = $_SESSION["url"];
else if(isset($_POST["base_url"]))
    $base_url = $_POST["base_url"];
else
{
    ?>
    <h1>Please enter the url of your server</h1>
    <form method="post">
        <input type="text" name="base_url" />
        <input type="submit" value="Send!">
    </form>
    <?php
    exit;
}


$_SESSION["url"] = $base_url;
$entry_point = "/api";

$url = null;
$vocab = null;
$link = false;


function is_url($url)
{
    $base_url = "http://localhost:5000";

    if(filter_var($base_url.$url,FILTER_VALIDATE_URL))
        return true;
    else if(filter_var($url,FILTER_VALIDATE_URL))
        return true;
    else return false;

}

if(isset($_POST["met"]) && isset($_POST["url"]))
{
    $r = new \JJG\Request($_POST["url"]);
    $r->setRequestType($_POST["met"]);
    $r->setRequestHeader("Content-Type: application/json");
    $r->setRequestHeader("Accept: application/json");
    if($_POST["met"] == "POST" || $_POST["met"] == "PUT" )
    {
        $post = array();
        foreach ($_POST as $key=>$value)
        {
            if($key == "url" || $key == "met")
                continue;
            else
                $post[$key]=$value;
        }
        $data_json = json_encode($post);
        $r->setPostFields($data_json);
    }

    try {
        $r->execute();
        if ($r->getHttpCode() == 200) {
            $result = json_decode($r->getResponse());
            if(property_exists($result,"members")) {
                $events=$result->{"members"};
                echo "Response:<br><br>";

                ?>
                <table>
                    <tr>
                <?php
                for($i=0;$i<count($events);$i++)
                {
                    $ev = (array) $events[$i];
                    echo "<td>";
                    foreach ($ev as $p_name=>$p_value)
                    {
                        if(is_url($p_value)) {
                            $link = true;
                            ?>
                            <?php echo $p_name; ?>: <a href="javascript:updateURL('<?php echo $p_value; ?>')"><?php echo $p_value; ?></a><br>
                            <?php
                        } else
                                echo "$p_name: $p_value<br>";

                    }
                    echo "</td>";
                    if($i%5==4)
                        echo "</tr><tr>";
                }
                ?>
                    </tr>
                </table>
                <?php
            } else {
                $ev = (array) $result;
                echo "Response:<br><br>";
                foreach ($ev as $p_name=>$p_value)
                {
                    if(is_url($p_value)) {
                        $link = true;
                        ?>
                    <?php echo $p_name; ?>: <a href="javascript:updateURL('<?php echo $p_value; ?>')"><?php echo $p_value; ?></a><br>
                    <?php
                    } else
                        echo "$p_name: $p_value<br>";

                }
            }
        } else if($r->getHttpCode() == 201)
        {
            echo "Success!<br>";
            $result = json_decode($r->getResponse());


                $ev = (array) $result;
            echo "Response:<br><br>";

            foreach ($ev as $p_name=>$p_value)
                {
                    if(is_url($p_value)) {
                        $link = true;
                        ?>
                        <?php echo $p_name; ?>: <a href="javascript:updateURL('<?php echo $p_value; ?>')"><?php echo $p_value; ?></a><br>
                        <?php
                } else
                    echo "$p_name: $p_value<br>";

                }



        } else if($r->getHttpCode() == 400) {
            echo $r->getResponse();
        } else {
            echo "Api not reachable. ".$r->getHttpCode();
        }
    }catch (HttpException $ex) {
        echo $ex;
    }
} else $url = $entry_point;

$r = new \JJG\Request($base_url.$entry_point);
$r->setRequestType( "GET");
try {
    $r->execute();
    if ($r->getHttpCode() == 200) {
        $result = get_object_vars(json_decode($r->getResponse()));
        $headers = $r->getHeader();
        //$link = $headers["link"];
        $vocab = "http://localhost:5000/api/vocab"; //TODO cant get vocab from server!!!
    } else
        echo "Api not reachable. ".$r->getHttpCode();
}catch (HttpException $ex) {
    echo $ex;
}

$r1 = new \JJG\Request($vocab);
$r1->setRequestType("GET");
try {
    $r1->execute();
    if ($r1->getHttpCode() == 200) {
        $result = json_decode($r1->getResponse());
        if($link)
            echo "<h3>First select an Action, then click on the corresponding link</h3>";
        echo "<h1>Action:</h1>";
        $support=$result->{"supportedClass"};
        ?>
        <script type="text/javascript">
            function makeVisible(elem)
            {
                for(var a=1;a<elem.children.length;a++)
                    document.getElementById(elem.children[a].value).style.display = "none";
                document.getElementById(elem.children[elem.selectedIndex].value).style.display = "block";
            }

        </script>

        <select id="class_picker" onchange="makeVisible(this)">
            <option value="">Please Select...</option>
            <?php
            for($i = 0; $i< count($support);$i++) {
                $ops = $support[$i]->{"supportedOperation"};
                if (count($ops) != 0) {
                    if (property_exists($support[$i], "hydra:title"))
                        $title = $support[$i]->{"hydra:title"};
                    else if (property_exists($support[$i], "label"))
                        $title = $support[$i]->{"label"};
                    echo "<option value='".$support[$i]->{"@id"}."' >$title</option>";
                }
            }
            ?>
        </select>
        <?php

        for($i = 0; $i< count($support);$i++)
        {
            $ops = $support[$i]->{"supportedOperation"};

                if(count($ops) != 0) {

                echo "<div id='".$support[$i]->{"@id"}."' style='display: none;'>";

                if(property_exists($support[$i],"hydra:title"))
                    echo "<h2>".$support[$i]->{"hydra:title"}."</h2>";
                else if (property_exists($support[$i],"label"))
                    echo "<h2>".$support[$i]->{"label"}."</h2>";

                ?>
                <select onchange="makeVisible(this)">
                    <option value="">Please Select...</option>
                    <?php
                    for($j=0;$j<count($ops);$j++) {
                        $method = $ops[$j]->{"method"};
                        echo "<option value='".$ops[$j]->{"@id"}."'>". $ops[$j]->{"label"}." [$method]</option>";
                    }
                    ?>
                </select>
                <?php

                for($j=0;$j<count($ops);$j++) {
                    echo "<div id='".$ops[$j]->{"@id"}."' style='display: none;'>";
                    echo "<p>" . $ops[$j]->{"label"} . "</p>";
                    if ($ops[$j]->{"expects"} == null) {

                        ?>
                        <form method='POST'>
                            <label for'url'>URL: </label>
                            <input type='text' name='url' style="width: 500px;" value='<?php echo $base_url.$url; ?>'><br>
                            <label for'met'>Method: </label>
                            <input type='text' name='met' style="width: 50px;" value='<?php echo $ops[$j]->{"method"}; ?>'><br>
                            <input type='submit' value='Go' >
                        </form>
                        <?php
                    } else {
                        $class = null;
                        for($k=0;$k<count($support);$k++)
                        {
                            if($support[$k]->{"@id"} === $ops[$j]->{"expects"})
                            {
                                $class = $support[$k];
                                break;
                            }
                        }
                        if($class == null)
                            echo "An error occured";
                        else {
                            ?>
                            <form method='POST'>
                                <label for'url'>URL: </label>
                                <input type='text' name='url' style="width: 500px;" value='<?php echo $base_url.$url; ?>'><br>
                                <label for'met'>Method: </label>
                                <input type='text' name='met' style="width: 50px;" value='<?php echo $ops[$j]->{"method"}; ?>'><br>
                                <?php
                                for($l=0;$l<count($class->{"supportedProperty"});$l++)
                                {
                                    $p = $class->{"supportedProperty"}[$l];
                                    echo "<label for='".$p->{"hydra:title"}."'>".$p->{"hydra:description"}."</label><br>";
                                    echo "<input type='text' name='".$p->{"hydra:title"}."' ><br>";
                                }
                                ?>
                            <input type='submit' value='Go' >
                            </form>
                            <?php
                        }
                        echo "<br><br>";

                    }
                    echo "</div>";
                }
                echo "</div>";
            }
        }
    } else
        echo "Api not reachable. ".$r1->getHttpCode()."<br>".$r1->getResponse();

} catch (HttpException $ex) {
    echo $ex;
}

?>


</body>
</html>
