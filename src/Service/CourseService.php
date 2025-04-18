<?php

namespace App\Service;

use App\Dto\CourseDto;
use App\Entity\Course;
use App\Exception\CourseException;
use App\Exception\CourseValidationException;
use App\Repository\CourseRepository;
use App\Security\User;

class CourseService
{
    public function __construct(
        private CourseRepository $courseRepository,
        private BillingClient $billingClient,
    ) {
    }

    public function newCourse(CourseDto $course, User $user): bool
    {
        if ($course->getType() == 'free') {
            $course->setPrice(null);
        }
        $result = $this->billingClient->newCourse($user->getApiToken(), $course);
        $exception = $this->checkSuccessCreateOrUpdateCourse($result);
        if ($exception !== null) {
            throw $exception;
        }
        $newCourse = new Course();
        $newCourse->setTitle($course->getTitle())
            ->setCharsCode($course->getCode())
            ->setDescription($course->getDescription());
        return $this->courseRepository->persistAndFlush($newCourse);
    }

    public function editCourse(string $code, CourseDto $course, User $user): bool
    {
        if ($course->getType() == 'free') {
            $course->setPrice(null);
        }
        $result = $this->billingClient->editCourse($user->getApiToken(), $code, $course);
        $exception = $this->checkSuccessCreateOrUpdateCourse($result);
        if ($exception !== null) {
            throw $exception;
        }
        $editCourse =$this->courseRepository->findOneBy(['chars_code' => $code]);
        if ($editCourse === null) {
            return false;
        }
        $editCourse->setTitle($course->getTitle())
            ->setCharsCode($course->getCode())
            ->setDescription($course->getDescription());
        return $this->courseRepository->persistAndFlush($editCourse);
    }

    private function checkSuccessCreateOrUpdateCourse(array $result): ?\Exception
    {
        if (!isset($result['success']) || !$result['success']) {
            if (isset($result['errors'])) {
                return new CourseValidationException($result['message'], $result['code'], $result['errors']);
            }
            $message = 'Internal server error';
            if (isset($result['message'])) {
                $message = $result['message'];
            }
            return new CourseException($message);
        }
        return null;
    }

    public function getFullCourse(Course $course): ?CourseDto
    {
        $courseArray = $this->billingClient->getCourse($course->getCharsCode());
        if (isset($courseArray['error_code'])) {
            return null;
        }
        $fullCourse = new CourseDto();
        $fullCourse->setId($course->getId())
            ->setTitle($course->getTitle())
            ->setDescription($course->getDescription())
            ->setCode($course->getCharsCode())
            ->setType($courseArray['type'])
            ->setPrice($courseArray['price'] ?? null);
        return $fullCourse;
    }
}