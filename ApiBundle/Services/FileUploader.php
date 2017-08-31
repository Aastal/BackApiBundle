<?php

namespace Geoks\ApiBundle\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Geoks\ApiBundle\Utils\ImageUtil;
use Geoks\ApiBundle\Utils\StringUtils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Kernel;

class FileUploader
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * @var ImageUtil
     */
    private $imageUtil;

    /**
     * @var Aws
     */
    private $aws;

    /**
     * @var array
     */
    private $filters;

    /**
     * @var array
     */
    private $vichMapping;

    public function __construct(EntityManager $em, Kernel $kernel, StringUtils $stringUtils, ImageUtil $imageUtil, Aws $aws, $filters, $vichMapping)
    {
        $this->em = $em;
        $this->kernel = $kernel;
        $this->stringUtils = $stringUtils;
        $this->imageUtil = $imageUtil;
        $this->aws = $aws;
        $this->filters = $filters;
        $this->vichMapping = $vichMapping;
    }

    public function resizeUpload()
    {
        // If thumb config is defined
        if ($sizes = $this->filters["resize_thumb"]["filters"]) {

            $system = new Filesystem();
            $root = $this->kernel->getRootDir();

            $metas = $this->em->getMetadataFactory()->getAllMetadata();
            $reader = new AnnotationReader();

            $fsaws = $this->aws->getS3Instance();

            // Search in all the entities
            /** @var ClassMetadata $meta */
            foreach ($metas as $meta) {

                echo $meta->getName();
                echo '<br />';

                $classReflection = $meta->getReflectionClass();

                // Check if the entity can upload a file
                if ($reader->getClassAnnotation($classReflection, "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {

                    $find = false;
                    foreach ($classReflection->getProperties() as $reflectionProperty) {

                        if (!$find && $annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\FilePath")) {

                            $find = true;
                            $path = $annotation->path;
                            $target = $this->stringUtils->getEndOfString("/", $this->vichMapping[$path]["uri_prefix"]);

                            $files = $fsaws->listKeys($target);

                            foreach ($files as $file) {

                                // Resize the file, depend of the project config
                                foreach ($sizes as $key => $size) {
                                    $filename = $this->stringUtils->getEndOfString("_", $file);

                                    if (!$fsaws->has($target . "/thumb_" . $key . "_" . $this->stringUtils->getEndOfString("/", $filename))) {

                                        $system->mkdir($root . "/../web/assets/$target");
                                        $newFile = $fsaws->get($file);
                                        $system->dumpFile($root . "/../web/assets/" . $newFile->getName(), $newFile->getContent());

                                        $newFile = new File($root . "/../web/assets/" . $newFile->getName());
                                        $system->copy($newFile, $root . "/../web/assets/$target" . "/thumb_" . $key . "_" . $newFile->getFilename());
                                        $newFile = new File($root . "/../web/assets/$target" . "/thumb_" . $key . "_" . $newFile->getFilename());

                                        $resize = $this->imageUtil->resizeImage($newFile, $size["size"][0], $size["size"][1]);

                                        if ($resize) {
                                            $fsaws->write($target . "/" . $newFile->getFilename(), file_get_contents($newFile));

                                            echo $newFile->getFilename();
                                            $system->remove($root . "/../web/assets/$target/" . $this->stringUtils->getEndOfString("/", $file));
                                            $system->remove($root . "/../web/assets/$target/" . $newFile->getFilename());
                                        } else {
                                            echo "error image : " . $newFile->getFilename();
                                        }
                                    } else {
                                        echo "=";
                                    }
                                }
                            }
                        }
                    }

                    echo '<br />';
                }
            }
        }
    }

    public function upload(UploadedFile $file, $targetDir)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();

        $file->move($targetDir, $fileName);

        return $fileName;
    }
}
