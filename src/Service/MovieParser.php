<?php

namespace App\Service;

use App\Entity\Movie;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;

class MovieParser extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

    }

    /**
     * @param Movie $movie
     */
    public function parseDescription(Movie $movie): ?Movie
    {
        $client = HttpClient::create();
        $omdbapi_key = $this->getParameter("app.omdbapi_key");
        $response = $client->request('GET', 'https://www.omdbapi.com/?apikey='.$omdbapi_key.'&t='.$movie->getTitle());
        $content = $response->toArray();
        if($movie->getScore()>10) {
            $movie->setScore(10);
        } else if($movie->getScore()<0) {
            $movie->setScore(0);
        }
        if ($content['Response'] == "True") {
            $movie->setDescription($content['Plot']);
            $movie->setVotersNumber(intval(str_replace(",", "", $content['imdbVotes']))+1);
            $movie->setTitle($content['Title']);
            $movie->setScore((floatval($content['imdbRating'])*($movie->getVotersNumber()-1)+$movie->getScore())/$movie->getVotersNumber());
            return $movie;
        } else {
            return null;
        }

    }
}