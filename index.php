<?php

function loadThunderstoreAPI()
{
    $json = file_get_contents("thunderstore.json");
    $x = json_decode($json, true);
    return $x;
}

function refresh()
{
    header("Location: $uri");
}

class Mods
{
    function __construct(Array $mod)
    {
        $api = loadThunderstoreAPI();
        $this->icon = "";

        $this->depstring = $mod["name"];


        $split = explode("-", $this->depstring);

        $this->author = $split[0];
        $this->name = $split[1];


        $this->major = $mod["version"]["major"];
        $this->minor = $mod["version"]["minor"];
        $this->patch = $mod["version"]["patch"];

        $this->verstring = sprintf("%d.%d.%d", $this->major, $this->minor, $this->patch);

        $this->enabled = $mod["enabled"];

        $this->fullstring = $this->depstring . "-" . $this->verstring;

        foreach ($api as $entry)
        {
            if ($entry["full_name"] == $this->depstring)
            {
                foreach($entry["versions"] as $version)
                {
                    if ($version["version_number"] == $this->verstring)
                        $this->icon = $version["icon"];
                }
            }
        }
    }

    public $icon;

    public $author;
    public $name;
    public $depstring;

    public $major;
    public $minor;
    public $patch;
    public $verstring;

    public $fullstring;

    public $enabled;
}

class Profile
{
    function __construct(Array $yaml)
    {
        $this->name = $yaml["profileName"];

        $this->mods = array();

        foreach ($yaml["mods"] as $mod)
            $this->mods[] = new Mods($mod);
    }

    function generateTable()
    {
        foreach ($this->mods as $mod)
        {   
            echo "<tr>";
            $logo = "";
            if (filter_var($mod->icon, FILTER_VALIDATE_URL))
                $logo = "<img src=\"$mod->icon\">";

            echo "<td>$logo</td>";
            echo "<td>$mod->name</td>";
            echo "<td>$mod->author</td>";
            echo "<td>$mod->verstring</td>";
            echo "<td>$mod->fullstring</td>";
            $checked = $mod->enabled ? "checked" : "";
            echo "<td><input type=\"checkbox\" $checked name=\"$mod->depstring\"></td>";
            $input = "<button type=\"submit\" name=\"delete\" value=\"$mod->fullstring\">Delete</button>";
            echo "<td><form method=\"post\">$input</form></td>";
            echo "</tr>";
        }
    }

    function delete(String $modstr)
    {
        foreach ($this->mods as $index => $mod)
        {
            if ($mod->fullstring == $modstr)
            {
                unset($this->mods[$index]);
            }
        }
    }

    public $name;
    public $mods;
}

$FORM_T = $_POST;
$profile = false;
$yaml = false;
$uri = $_SERVER['REQUEST_URI'];

session_start();
if (isset($FORM_T["url"]) and filter_var($FORM_T["url"], FILTER_VALIDATE_URL))
{
    $url = $FORM_T["url"];
    $yaml = file_get_contents($url);
    $yaml = yaml_parse($yaml) or false;
}
else if (isset($_FILES["file"]))
{
    if (!($_FILES["file"]["size"] > 500000))
    {    
        $file = $_FILES["file"]["tmp_name"];
        $yaml = file_get_contents($file);
        $yaml = yaml_parse($yaml) or false;
    }
}


if ($yaml)
{

    $profile = new Profile($yaml);
    $_SESSION["user_profile"] = $profile;

    refresh();
}
else if (array_key_exists("user_profile", $_SESSION))
{
    $profile = $_SESSION["user_profile"];
}

if (isset($profile))
{
    if (isset($FORM_T["delete"]))
    {
        $profile->delete($FORM_T["delete"]);
        refresh();
    }
}

?>


<!DOCTYPE html>
<html>

    <head>
        <title>Risk of Rain 2 Modpack Builder</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    </head>

    <body>
        <style>
            :root
            {
                --menu-size: 8%;
                --table-size: calc(100% - var(--menu-size));

                --form-spacing: 30px;

                --input-width: 230px;
            }

            html, body, .container
            {
                height: 100%;
                margin: 0;
            }

            .container div
            {
                width: 80%;
                margin-top: 20px;
                margin-left: auto;
                margin-right: auto;
            }

            @media screen and (max-width:1300px)
            {
                .container div
                {
                    width: 100%;
                }
            }

            .menu-bar { height: var(--menu-size); }

            .menu-bar form {
                float: left; 
                margin-right: var(--form-spacing);
                margin-left: var(--form-spacing);
            }

            .menu-bar form input { width: var(--input-width); }
            .menu-bar form input[type=text] { width: calc(var(--input-width) - 18px) }

            .mod-table { height: var(--table-size); }

            .mod-table table { width: 100%; }

            .mod-table table td img { width: 128px; }

            th, td { border: 1px solid black; }

        </style>

        <div class="container">
            <div class="menu-bar">
                <form method="post">
                    <input type="text" name="url"><br>
                    <input type="submit" value="Load from URL">
                </form>

                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="file"><br>
                    <input type="submit" value="Load from file">
                </form>


                <!-- <form method="post">
                    <input type="submit" name="export-yaml" value="Export as yaml"><br>
                    <input type="submit" name="export-gist" value="Export to gist">
                </form><br> -->

            </div>
            <div class="mod-table">
                <table>
                <tr>
                    <th>icon</th>
                    <th>name</th>
                    <th>author</th>
                    <th>version</th>
                    <th>dependency string</th>
                    <th>enabled</th>
                    <th>deleted</th>
                </tr>
                <?php if ($profile) $profile->generateTable(); ?>
                </table> 
            </div>
        </div>
    </body>

</html>