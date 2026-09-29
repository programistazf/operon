<?php

declare(strict_types=1);

namespace App\School\Infrastructure\Fixtures;

use App\School\Domain\Matching\NameNormalizer;
use App\School\Domain\School;
use App\School\Domain\SchoolType;

final class SchoolDataset
{
    private const array ROWS = [
        ['I Liceum Ogólnokształcące im. Adama Mickiewicza', ['LO Mickiewicza', 'Mickiewicz', 'I LO', 'Pierwsze LO'], 'Warszawa', SchoolType::Liceum],
        ['XIV Liceum Ogólnokształcące im. Stanisława Staszica', ['Staszic', 'XIV LO', '14 LO', 'Staszica'], 'Warszawa', SchoolType::Liceum],
        ['Zespół Szkół Elektronicznych i Informatycznych', ['ZSEI', 'Elektronik', 'ZSEiI'], 'Warszawa', SchoolType::Technikum],
        ['Liceum Ogólnokształcące nr 5 im. Józefa Wybickiego', ['V LO', 'Piąte LO', 'Wybicki', 'LO 5'], 'Kraków', SchoolType::Liceum],
        ['II Liceum Ogólnokształcące im. Marii Konopnickiej', ['Konopnicka', 'Drugie LO', 'II LO', 'LO Konopnickiej'], 'Gdańsk', SchoolType::Liceum],
        ['Technikum Informatyczne nr 1', ['TI 1', 'Pierwsze Informatyczne', 'Technikum IT'], 'Wrocław', SchoolType::Technikum],
        ['Liceum Ogólnokształcące im. Mikołaja Kopernika', ['Kopernik', 'LO Kopernika'], 'Poznań', SchoolType::Liceum],
        ['Zespół Szkół Technicznych i Ogólnokształcących', ['ZSTiO', 'Techniczne i Ogólnokształcące', 'ZSTO'], 'Łódź', SchoolType::Technikum],
        ['III Liceum Ogólnokształcące im. Juliusza Słowackiego', ['Słowacki', 'Trzecie LO', 'III LO', 'LO Słowackiego'], 'Kraków', SchoolType::Liceum],
        ['Liceum Ogólnokształcące im. Henryka Sienkiewicza', ['Sienkiewicz', 'LO Sienkiewicza', 'Sienkiewicz LO'], 'Katowice', SchoolType::Liceum],
        ['Technikum Mechatroniczne', ['Mechatronika', 'TM', 'Tech Mechatroniczne'], 'Gdynia', SchoolType::Technikum],
        ['Liceum Ogólnokształcące im. Stefana Żeromskiego', ['Żeromski', 'LO Żeromskiego', 'Zeromski'], 'Szczecin', SchoolType::Liceum],
    ];

    public static function schools(NameNormalizer $normalizer): array
    {
        $schools = [];
        foreach (self::ROWS as [$officialName, $aliases, $city, $type]) {
            $school = School::register($officialName, $city, $type, $normalizer);
            foreach ($aliases as $alias) {
                $school->addAlias($alias, $normalizer);
            }
            $schools[] = $school;
        }

        return $schools;
    }
}
