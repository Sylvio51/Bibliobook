<?php
// src/Controller/BookController.php

namespace App\Controller;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

#[Route('/books', name: 'books_')]
class BookController extends AbstractController
{
    #[Route('/books/manage', name: 'books_manage', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function manage(): Response
    {
        return $this->render('books/manage.html.twig');
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $books = $em->getRepository(Book::class)->findAll();

        $data = [];
        foreach ($books as $book) {
            $data[] = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => $book->getAuthor(),
                'summary' => $book->getSummary(),
                'createdAt' => $book->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $book = new Book();
        $book->setTitle($data['title'] ?? '');
        $book->setAuthor($data['author'] ?? '');
        $book->setSummary($data['summary'] ?? null);
        $book->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->persist($book);
        $em->flush();

        return $this->json([
            'id' => $book->getId(),
            'message' => 'Book created successfully',
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $book = $em->getRepository(Book::class)->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $book->setTitle($data['title'] ?? $book->getTitle());
        $book->setAuthor($data['author'] ?? $book->getAuthor());
        $book->setSummary($data['summary'] ?? $book->getSummary());

        $errors = $validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->flush();

        return $this->json(['message' => 'Book updated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $book = $em->getRepository(Book::class)->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $em->remove($book);
        $em->flush();

        return $this->json(['message' => 'Book deleted successfully']);
    }
}
