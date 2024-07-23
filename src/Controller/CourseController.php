<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use App\Service\PurchaseControl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/courses')]
class CourseController extends AbstractController
{
    public function __construct(
        private PurchaseControl $purchaseControl,
        private BillingClient $billingClient,
    ) {
    }

    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): Response
    {
        $coursesData = $courseRepository->findAll();
        $courses = [];

        foreach ($coursesData as $courseData) {
           $courses[] = $this->purchaseControl->getDataCourse($courseData);
        }

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user !== null) {
            $userBalance = $this->billingClient->userCurrent($user->getApiToken())['balance'];
        } else {
            $userBalance = 0;
        }

        $course = $this->purchaseControl->getDataCourse($course);

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'user_balance' => $userBalance
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pay', name: 'app_course_pay')]
    public function pay(Course $course): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $billingResponse = $this->billingClient->payCourse($user->getApiToken(), $course->getCharsCode());

        if (isset($billingResponse['error_code'])) {
            $this->addFlash('error', $billingResponse['message']);
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        } else {
            $this->addFlash('success', $billingResponse['message']);
        }

        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER); 
    }
}
