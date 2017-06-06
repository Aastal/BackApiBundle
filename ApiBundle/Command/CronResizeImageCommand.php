<?php

namespace Geoks\ApiBundle\Command;

use Aws\S3\S3Client;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Gaufrette\Adapter\AmazonS3;
use Gaufrette\Adapter\AwsS3;
use Liip\ImagineBundle\Imagine\Cache\Resolver\AmazonS3Resolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CronResizeImageCommand extends CronTaskCommand
{
    protected $name = "resize-image";
    protected $description = "Resize images in amazone s3 server.";

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // If thumb config is defined
        if ($sizes = $this->getContainer()->getParameter('liip_imagine.filter_sets')["resize_thumb"]["filters"]) {

            $stringManager = $this->getContainer()->get('geoks.utils.string_manager');
            $imgUtil = $this->getContainer()->get('geoks.utils.image');
            $system = new Filesystem();
            $root = $this->getContainer()->get('kernel')->getRootDir();

            $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
            $metas = $em->getMetadataFactory()->getAllMetadata();
            $reader = new AnnotationReader();

            $vichMappings = $this->getContainer()->getParameter('vich_uploader.mappings');

            $fsaws = $this->getContainer()->get('geoks.api.aws')->getS3Instance();

            // Search in all the entities
            foreach ($metas as $meta) {

                /** @var ClassMetadata $meta */
                $classReflection = $meta->getReflectionClass();

                // Check if the entity can upload a file
                if ($reader->getClassAnnotation($classReflection, "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {

                    $find = false;
                    foreach ($classReflection->getProperties() as $reflectionProperty) {
                        if (!$find && $annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\FilePath")) {

                            $find = true;
                            $path = $annotation->path;
                            $target = $stringManager->getEndOfString("/", $vichMappings[$path]["uri_prefix"]);

                            $files = $fsaws->listKeys($target);

                            foreach ($files as $file) {

                                // Resize the file, depend of the project config
                                foreach ($sizes as $key => $size) {
                                    $filename = $stringManager->getEndOfString("_", $file);

                                    if (!$fsaws->has($target . "/thumb_" . $key . "_" . $stringManager->getEndOfString("/", $filename))) {

                                        $system->mkdir($root . "/../web/assets/$target");
                                        $newFile = $fsaws->get($file);
                                        $system->dumpFile($root . "/../web/assets/" . $newFile->getName(), $newFile->getContent(), 0777);

                                        $newFile = new File($root . "/../web/assets/" . $newFile->getName());
                                        $system->copy($newFile, $root . "/../web/assets/$target" . "/thumb_" . $key . "_" . $newFile->getFilename());
                                        $newFile = new File($root . "/../web/assets/$target" . "/thumb_" . $key . "_" . $newFile->getFilename());

                                        $imgUtil->resizeImage($newFile, $size["size"][0], $size["size"][1]);
                                        $fsaws->write($target . "/" . $newFile->getFilename(), file_get_contents($newFile));

                                        $output->writeln($newFile->getFilename());
                                        $system->remove($root . "/../web/assets/$target/" . $stringManager->getEndOfString("/", $file));
                                        $system->remove($root . "/../web/assets/$target/" . $newFile->getFilename());
                                    } else {
                                        $output->write("=");
                                    }
                                }
                            }
                        }
                    }
                }

                $output->writeln($meta->getName());
            }
        }
    }
}
