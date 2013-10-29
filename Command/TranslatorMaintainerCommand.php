<?php
// TranslatorMaintainerCommand.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 26/10/13
 * Time: 13:44
 * To change this template use File | Settings | File Templates.
 */

namespace Gizlab\Bundle\TranslationBundle\Command;


use Gizlab\Bundle\TranslationBundle\Entity\Language;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TranslatorMaintainerCommand
 * @package Gizlab\Bundle\TranslationBundle\Command
 */
class TranslatorMaintainerCommand extends ContainerAwareCommand
{

    /**
     * @see Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand::configure()
     */
    protected function configure()
    {
        $this->setName('gizlab:translation:maintain')
            ->setDescription('Maintain data in database')
            ->addArgument('action', InputArgument::REQUIRED, 'What to do? init, ..');
    }

    /**
     * @see \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand::execute()
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');

        $entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $defaultLocale = $this->getContainer()->getParameter('locale');


        if ($action == 'init') {

            /*
             * Get root of application
             */
            $kernelRoot = $this->getContainer()->getParameter('kernel.root_dir');
            $translationDirectory = $kernelRoot . '/Resources/translations';

            $error = false;
            if (!file_exists($translationDirectory)){
                $error = true;
                $andDirectoryDoesntExists  = true;
            } else {
                $localesFromFiles = $this->getLocalesFromFiles($translationDirectory);
                if (count($localesFromFiles) == 0)
                    $error = true;
            }

            /*
             * Try to create needed files
             */
            if ($error) {
                /* @var $dialog DialogHelper */
                $dialog = $this->getHelper('dialog');

                if (!$dialog->askConfirmation($output, 'You don\'t have any translation files. Do you want to create then?', false)) {

                    return false;
                }

                if ($andDirectoryDoesntExists) {
                    mkdir($translationDirectory, 0777);
                }

                // Enter namespace
                $namespace = $dialog->ask($output, 'Please enter namespace for processing translations, ex. MyBundle: ');

                $languages = $dialog->ask($output, 'Please enter other locales to generate translations, ex.[de,fr]:');

                $arguments = array('namespace' => $namespace, 'locales'=>$languages, '--default-locale'=>$defaultLocale);
                $commandInput = new ArrayInput($arguments);

                $command = $this->getApplication()->find('gizlab:translation:dump');
                $result = $command->run($commandInput, $output);

            }

            // Reload locales from files
            $localesFromFiles = $this->getLocalesFromFiles($translationDirectory);

            /*
             * Processing database
             */

            foreach($localesFromFiles as $localeFromFiles){

                $language = $entityManager->getRepository('GizlabTranslationBundle:Language')->findOneById(strtolower($localeFromFiles));
                if ($language == null){
                    // create new language
                    $language = new Language();
                    $language->setId(strtolower($localeFromFiles))
                        ->setLocale('')
                        ->setLabel(strtoupper($localeFromFiles));
                }

                if (strtolower($localeFromFiles) == $defaultLocale){
                    $language->setIsDefault(true);
                } else {
                    $language->setIsDefault(false);
                }

                $entityManager->persist($language);

            }

            $entityManager->flush();

        }

    }

    /**
     * Get locales from files
     * @param $translationDirectory
     * @return array
     */
    private function getLocalesFromFiles($translationDirectory)
    {

        $directoryHandler = opendir($translationDirectory);
        $localesFromFiles = array();
        while($file = readdir($directoryHandler)){
            if (!in_array($file, array('.', '..'))){
                if (preg_match('/\.(.*)\.yml/i', $file, $matches)){
                    $localesFromFiles[] = $matches[1];
                }
            }
        }
        closedir($directoryHandler);

        return $localesFromFiles;
    }


}