<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\MovieType;
use App\Service\MovieParser;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
#[Route('/')]
class MovieController extends AbstractController
{
    #[Route('/', name: 'movie_index', methods: ['GET'])]
    public function index(MovieRepository $movieRepository): Response
    {
        return $this->render('movie/index.html.twig', [
            'movies' => $movieRepository->findBy(
                array(),
                array('score' => 'DESC','title' => 'ASC')),
        ]);
    }
    
    #[Route('/csv', name: 'csv_flood')]
    public function csv(Request $request,ManagerRegistry $doctrine, EntityManagerInterface $entityManager): Response
    {
        /*
        $form = $this->createFormBuilder()
            ->add('file', FileType::class, array('label' => 'Fichier CSV : '))
            ->add('submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            $file = fopen($file, 'r');
            while (($line = fgetcsv($file)) !== false) {
                if($entityManager->findOneBy(['title' => $line[0]]) == null){
                    $movie = new Movie();
                    $movie->setTitle($line[0]);
                    $movie->setDescription($line[1]);
                    $movie->setScore($line[2]);
                    $movie->setVotersNumber($line[3]);
                    $entityManager->persist($movie);
                }
            }
            fclose($file);
            $entityManager->flush();
            return $this->redirectToRoute('form_index');
        }
        */
        return $this->render('movie/csv.html.twig');
    }

    #[Route('/new', name: 'movie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, MovieParser $movieParser): Response
    {
        $movie = new Movie();
        $form = $this->createForm(MovieType::class, $movie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $movie = $form->getData();
            $movie=$movieParser->parseDescription($movie);
            if($movie!=null) {
                $entityManager->persist($movie);
                $entityManager->flush();
            } else {
                return $this->renderForm('movie/new.html.twig', [
                    'movie' => $movie,
                    'form' => $form,
                    'error' => true,
                ]);
            }


            return $this->redirectToRoute('movie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('movie/new.html.twig', [
            'movie' => $movie,
            'form' => $form,
            'error' => false,
        ]);
    }

    #[Route('/{id}', name: 'movie_show', methods: ['GET'])]
    public function show(Movie $movie): Response
    {
        return $this->render('movie/show.html.twig', [
            'movie' => $movie,
            'error' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'movie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Movie $movie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MovieType::class, $movie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('movie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('movie/edit.html.twig', [
            'movie' => $movie,
            'form' => $form,
            'error' => false,
        ]);
    }

    #[Route('/{id}', name: 'movie_delete', methods: ['POST'])]
    public function delete(Request $request, Movie $movie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$movie->getId(), $request->request->get('_token'))) {
            if($_POST["code_admin"]==$this->getParameter('app.admin_code')) {
                $entityManager->remove($movie);
                $entityManager->flush();
            } else {
                return $this->render('movie/show.html.twig', [
                    'movie' => $movie,
                    'error' => true,
                ]);
            }

        }

        return $this->redirectToRoute('movie_index', [], Response::HTTP_SEE_OTHER);
    }
}