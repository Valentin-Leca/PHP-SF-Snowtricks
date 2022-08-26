<?php

namespace App\Service;

use App\Entity\Trick;
use App\Entity\Video;

use http\Exception\BadUrlException;
use PharIo\Manifest\InvalidUrlException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadFile {

    private string $targetDirectory;
    private SluggerInterface $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger) {

        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file, $image) {

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            try {
                $file->move($this->targetDirectory, $fileName);
                $image->setImagename($fileName);
            } catch (FileException $e) {
                return $e->getMessage();
            }
    }

    public function videoId(Video $video) {

        parse_str(parse_url($video->getVideoname(), PHP_URL_QUERY), $videoId);

        if (isset($videoId)) {
            try {
                $video->setVideoname($videoId['v']);
            } catch (BadUrlException $e) {
                return $e->getMessage();
            }
        }
    }

    public function uploadFiles(Trick $trick) {

        foreach ($trick->getImages()->getValues() as $image) {

            $this->upload($image->getImagename(), $image);

            $trick->addImage($image);
        }

        foreach ($trick->getVideos()->getValues() as $video) {

            $this->videoId($video);

            $trick->addVideo($video);
        }
    }
}