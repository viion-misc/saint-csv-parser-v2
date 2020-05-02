<?php

namespace App\Parsers\GE;

use App\Parsers\CsvParseTrait;
use App\Parsers\ParseInterface;

/**
 * php bin/console app:parse:csv GE:Achievement
 */
class Achievement implements ParseInterface
{
    use CsvParseTrait;

    // the wiki output format / template we shall use
    const WIKI_FORMAT = "{Top}{{ARR Infobox Achievement
| Index       = {index}
| Patch       = {patch}
| Name        = {name}
| Icon        = {icon}.png
| Category    = {category}
| Type        = {type}
| Achievement Points = {points}
| Achievement = {description}

| Achievement Reward-Items = {item}
| Male Title = {titleMale}
| Female Title = {titleFeMale}

| Achievements Required for Reward = {achireq}

| Prior Achievement =
| Next Achievement  =
}}{Bottom}";
    public function parse()
    {
        $patch = '5.21';

        // grab CSV files we want to use
        $AchievementCsv = $this->csv('Achievement');
        $ItemCsv = $this->csv('Item');
        $TitleCsv = $this->csv('Title');
        $ClassJobCsv = $this->csv('ClassJob');
        $PlaceNameCsv = $this->csv('PlaceName');
        $QuestCsv = $this->csv('Quest');
        $MapCsv = $this->csv('Map');
        $TerritoryTypeCsv = $this->csv('TerritoryType');
        $AchievementCategoryCsv = $this->csv('AchievementCategory');
        $AchievementKindCsv = $this->csv('AchievementKind');


        // (optional) start a progress bar
        $this->io->progressStart($AchievementCsv->total);

        // loop through data
        foreach ($AchievementCsv->data as $id => $Achievement) {
            $this->io->progressAdvance();

            // if I want to use pywikibot to create these pages, this should be true. Otherwise if I want to create pages
            // manually, set to false
            $Bot = "true";

            $index = $Achievement['id'];
            $name = $Achievement['Name'];
            $descriptionPre = $Achievement['Description'];
            $oldwords = ["gladiator", "pugilist", "marauder", "lancer", "archer", "conjurer", "thaumaturge", "carpenter", "blacksmith", "armorer", "goldsmith", "leatherworker", "weaver", "alchemist", "culinarian", "miner", "botanist", "fisher", "paladin", "monk", "monk", "dragoon", "bard", "white mage", "black mage", "arcanist", "summoner", "scholar", "rogue", "ninja", "machinist", "dark knight", "astrologian", "samurai", "black mage", "red mage", "blue mage", "gunbreaker", "dancer"];
            $newwords = ["[[Gladiator]]","[[Pugilist]]","[[Marauder]]","[[Lancer]]","[[Archer]]","[[Conjurer]]","[[Thaumaturge]]","[[Carpenter]]","[[Blacksmith]]","[[Armorer]]","[[Goldsmith]]","[[Leatherworker]]","[[Weaver]]","[[Alchemist]]","[[Culinarian]]","[[Miner]]","[[Botanist]]","[[Fisher]]","[[Paladin]]","[[Monk]]","[[Warrior]]","[[Dragoon]]","[[Bard]]","[[White Mage]]","[[Black Mage]]","[[Arcanist]]","[[Summoner]]","[[Scholar]]","[[Rogue]]","[[Ninja]]","[[Machinist]]","[[Dark Knight]]","[[Astrologian]]","[[Samurai]]","[[Black Mage]]","[[Red Mage]]","[[Blue Mage]]","[[Gunbreaker]]","[[Dancer]]"];
            $description = str_replace($oldwords, $newwords, $descriptionPre);
            //if its a type 6 then add the link to the desciption for quests
            if ($Achievement['Type'] == 6) {
                $questkeyname = $QuestCsv->at($Achievement["Key"])['Name'];
                $description = str_ireplace($questkeyname, "[[". $questkeyname ."]]", $description);
                foreach(range(0,7) as $i) {
                    $questname = $QuestCsv->at($Achievement["Data[$i]"])['Name'];
                    $description = str_ireplace($questname, "[[". $questname ."]]", $description);
                }
            }
            //if its a type 2 change description like above then add the link for required achievement
            $reqachievement = [];
            if ($Achievement['Type'] == 2) {
                $reqachievement[0] = "". $AchievementCsv->at($Achievement["Key"])['Name'] ."";
                $reqachikeyname = $AchievementCsv->at($Achievement["Key"])['Name'];
                $description = str_ireplace($reqachikeyname, "[[". $reqachikeyname ."]]", $description);
                foreach(range(0,7) as $i) {
                    $reqachiname = $AchievementCsv->at($Achievement["Data[$i]"])['Name'];
                    $description = str_ireplace($reqachiname, "[[". $reqachiname ."]]", $description);
                    if (!empty($AchievementCsv->at($Achievement["Data[$i]"])['Name'])) {
                        $reqachievement[] = "". $AchievementCsv->at($Achievement["Data[$i]"])['Name'] ."";
                    }
                }
            }
            //
            $reqachievement = implode(" ,", $reqachievement);
            //if its a type 8 then replace the zone in description with the link
            if ($Achievement['Type'] == 8) {
                $keyname = $PlaceNameCsv->at($MapCsv->at($Achievement["Key"])['PlaceName'])['Name'];
                $description = str_ireplace($keyname, "[[". $keyname ."]]", $description);
            }
            $points = $Achievement['Points'];
            //sets title to nothing is there is no reward
            $titleMale = "";
            $titleFemale = "";
            if ($Achievement['Title'] != "0") {
                $titleMale = "". $TitleCsv->at($Achievement['Title'])['Masculine'] ."";
                $titleFemale = "". $TitleCsv->at($Achievement['Title'])['Feminine'] ."";
            }
            $item = $ItemCsv->at($Achievement['Item'])['Name'];
            //puts the correct amount of 00 before the icon id
            $icon = sprintf("%06d", $Achievement['Icon']);

            $type = $AchievementCategoryCsv->at($Achievement['AchievementCategory'])['Name'];
            $category = $AchievementKindCsv->at($AchievementCategoryCsv->at($Achievement['AchievementCategory'])['AchievementKind'])['Name'];


            // change the top and bottom code depending on if I want to bot the pages up or not. Places Patch on subpage
            if ($Bot == "true") {
                $Top = "{{-start-}}\n'''$name/Patch'''\n$patch\n{{-stop-}}{{-start-}}\n'''$name'''\n";
                $Bottom = "{{-stop-}}";
            } else {
                $Top = "http://ffxiv.gamerescape.com/wiki/$name\Patch?action=edit\n$patch\nhttp://ffxiv.gamerescape.com/wiki/$name?action=edit\n";
                $Bottom = "";
            };

            // Save some data
            $data = [
                '{Top}' => $Top,
                '{index}' => $index,
                '{patch}' => $patch,
                '{name}' => $name,
                '{item}' => $item,
                '{icon}' => $icon,
                '{points}' => $points,
                '{description}' => $description,
                '{titleMale}' => $titleMale,
                '{titleFemale}' => $titleFemale,
                '{type}' => $type,
                '{category}' => $category,
                '{achireq}' => $reqachievement,
                '{Bottom}' => $Bottom,
            ];

            // format using Gamer Escape formatter and add to data array
            // need to look into using item-specific regex, if required.
            $this->data[] = GeFormatter::format(self::WIKI_FORMAT, $data);
        };

        // save our data to the filename: GeMountWiki.txt
        $this->io->progressFinish();
        $this->io->text('Saving ...');
        //$info = $this->save('Achievement.txt', 20000);
        $info = $this->save("GeAchievementWiki - ". $patch .".txt", 9999999);

        $this->io->table(
            [ 'Filename', 'Data Count', 'File Size' ],
            $info
        );
    }
}