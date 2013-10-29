<?php
namespace Gizlab\Bundle\TranslationBundle\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Translation\Writer\TranslationWriter;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\DependencyInjection\Container;

use Symfony\Component\HttpKernel\Kernel;

use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 *
 * @author juriem
 *
 */
class TranslatorDumperCommand extends ContainerAwareCommand
{
    /**
     *
     * @var Kernel
     */
    private $kernel;


    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setName('gizlab:translation:dump')
            ->setDescription('Translation dumper')
            ->addArgument('namespace', InputArgument::REQUIRED, 'Root namespace for bundles')
            ->addArgument('locales', InputArgument::REQUIRED, 'Locale code or list of locales codes. Ex. fr,en,ru.')
            ->addOption('default-locale', null, InputOption::VALUE_OPTIONAL, 'Default locale')
            ->addOption('cleanup', null, InputOption::VALUE_NONE, 'Clean up unused translation ids');
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $kernel = $this->getContainer()->get('kernel');

        // Processing arguments
        // Get root namespace
        $namespace = $input->getArgument('namespace');

        // Get default locale
        if (($optionValue = $input->getOption('default-locale')) !== null) {

            $defaultLocale = $optionValue;
        } else {
            $defaultLocale = $this->getContainer()->getParameter('locale');
        }

        // Get locales

        $locales = explode(',', $input->getArgument('locales'));
        foreach ($locales as $locale) {
            if ($locale !== $defaultLocale) {
                $buffer[] = trim($locale);
            }
        }
        $locales = $buffer;


        // Get flag for consolidation of translations
        //$consolidateTranslations = $input->getOption('consolidate');
        $consolidateTranslations = true;

        // Cleanup flag
        $cleanUpUnused = $input->getOption('cleanup');

        // Get all bundles
        $bundles = $kernel->getBundles();
        $filteredBundles = array();
        foreach ($bundles as $bundleName => $bundleInstance) {

            // Filter bundles by namespace
            $className = get_class($bundleInstance);
            $bundleCatalogue = substr($className, 0, strlen($className) - strlen($bundleName) - 1);

            if (strpos($bundleCatalogue, $namespace) === 0) {
                $filteredBundles[Container::underscore($bundleName)] = str_replace('\\', DIRECTORY_SEPARATOR, $bundleCatalogue);
            }
        }
        // Add app root

        // Proccessing views in app catalogue
        $translationIds = $this->processingBundle();
        foreach ($filteredBundles as $bundleName => $bundleCatalogue) {

            $bundleTranslationIds = $this->processingBundle($bundleCatalogue);

            $translationIds = array_merge($translationIds, $bundleTranslationIds);
        }
        // Consolidate all values for ids
        $translationIds = array_unique($translationIds);

        $cnt = count($translationIds);
        $output->writeln("Founded $cnt translation ids");

        // Get translations for default locale from
        $translations = $this->getTranslations();

        // Processing all translations
        // Processing default translations
        if (array_key_exists($defaultLocale, $translations)) {
            // Cheking for new translation ids
            $buffer = array();
            foreach ($translationIds as $translationId) {
                if (!key_exists($translationId, $translations[$defaultLocale])) {
                    $buffer[$translationId] = $translationId;
                }
            }

            if (($cnt = count($buffer)) > 0) {
                $output->writeln("Founded $cnt new translation ids.");
                $buffer = array_merge($buffer, $translations[$defaultLocale]);
                $translations[$defaultLocale] = $buffer;
                unset($buffer);
            }

            if ($cleanUpUnused) {
                $buffer = array();
                foreach ($translations[$defaultLocale] as $id => $value) {
                    foreach ($translationIds as $translationId) {
                        if ($id === $translationId) {
                            $buffer[$id] = $value;
                            break;
                        }
                    }
                }

                if (($cnt = count($translations[$defaultLocale]) - count($buffer)) > 0) {
                    $output->writeln("Found $cnt unused translations. Processing cleang up.");
                    $translations[$defaultLocale] = $buffer;
                    unset($buffer);
                }
            }


            // Processing locales
            foreach ($locales as $locale) {
                if (!array_key_exists($locale, $translations)) {
                    $translations[$locale] = $translations[$defaultLocale];
                } else {
                    // Add new translation ids
                    $buffer = array();
                    foreach ($translations[$defaultLocale] as $id => $value) {
                        if (!array_key_exists($id, array_keys($translations[$locale]))) {
                            $buffer[$id] = $value;
                        }
                    }
                    if (count($buffer) > 0) {
                        $buffer = array_merge($buffer, $translations[$locale]);
                        $translations[$locale] = $buffer;
                        unset($buffer);
                    }

                    // Remove unused
                    if ($cleanUpUnused) {
                        $buffer = array();
                        foreach ($translations[$locale] as $id => $value) {
                            if (array_key_exists($id, $translations[$defaultLocale])) {
                                $buffer[$id] = $value;
                            }
                        }
                        $translations[$locale] = $buffer;
                    }
                }
            }

        } else {
            // Create translations for default locale
            $buffer = array();
            foreach ($translationIds as $translationId) {
                $buffer[$translationId] = $translationId;
            }
            $translations[$defaultLocale] = $buffer;
        }

        $locales = array_keys($translations);
        $catalogue = $this->getContainer()->get('kernel')->getRootDir() . '/Resources/translations';
        foreach ($locales as $locale) {
            $content = "# Translations\n";
            $content .= "# Generated at " . date('d.m.Y H:i:s') . "\n\n";

            $content .= Yaml::dump($translations[$locale]);

            $file = $catalogue . '/messages.' . $locale . '.yml';
            file_put_contents($file, $content);
        }
        $output->writeln("Done.");
    }

    /**
     * Processing bundle source files and getting translation ids
     *
     * @param string $className
     * @return array - Array of ids
     */
    protected function processingBundle($bundleCatalogue = null)
    {
        // Get src location
        if ($bundleCatalogue) {
            $srcDirectory = $this->getContainer()->get('kernel')->getRootDir() . '/../src/' . $bundleCatalogue;
        } else {
            $srcDirectory = $this->getContainer()->get('kernel')->getRootDir() . '/Resources/views';
        }
        $ids = array();
        if (file_exists($srcDirectory)) {
            $files = $this->getFiles($srcDirectory);

            foreach ($files as $filename) {
                $ids = array_merge($ids, $this->processingSourceFile($filename));
            }
        }
        return $ids;
    }

    /**
     *
     * @param string $catalogue
     * @param string $bundleName
     * @return Ambigous <multitype:, string, unknown, string, \Symfony\Component\Yaml\mixed, NULL, multitype:NULL , boolean, multitype:NULL \Symfony\Component\Yaml\mixed , number, mixed, number>
     */
    private function getTranslations($catalogue = null, $bundleName = 'app')
    {
        $result = array();

        $catalogue = $this->getTranslationsCatalogue($catalogue);

        // Check if translations exists
        if (file_exists($catalogue)) {

            $handler = opendir($catalogue);
            while ($file = readdir($handler)) {

                if (!in_array($handler, array('.', '..'))) {
                    if (preg_match('/(.*)\.yml/', $file, $matches)) {

                        preg_match('/(.*)\.(.*)/', $matches[1], $parts);

                        $content = file_get_contents($catalogue . '/' . $file);

                        try {
                            $translations = Yaml::parse($content);
                        } catch (\Exception $e) {
                            $translations = null;
                        }
                        if ($translations !== null) {
                            $result[$parts[2]] = $translations;
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function getTranslationsCatalogue($bundleCatalogue = null, $createCatalogue = false)
    {
        if ($bundleCatalogue === null) {
            $catalogue = $this->getContainer()->get('kernel')->getRootDir() . '/Resources/translations';
        } else {
            $catalogue = $this->getContainer()->get('kernel')->getRootDir() . '/../src/' . $bundleCatalogue . '/Resources/translations';
        }

        if (!file_exists($catalogue) && $createCatalogue) {
            mkdir($catalogue, 0777, true);
        }

        return $catalogue;
    }


    /**
     * Write translations into translation file
     *
     * @param string $bundleName
     * @param array $translations
     * @param string $locale
     * @return boolean
     */
    private function writeTranslations($bundleName, array $translations, $locale)
    {
        if (key_exists($locale, $translations)) {

            $catalogue = $this->getTranslationsCatalogue($bundleName, true);

            $content = "# Bundle: $bundleName\n";
            $content .= "# Locale: $locale\n";
            $content .= "# Gerated at " . date('d.m.Y H:i:s') . "\n\n";

            $content .= Yaml::dump($translations[$locale]);

            file_put_contents($catalogue . '/messages.' . $locale . '.yml', $content);
        }

        return true;
    }

    /**
     * Get source files for bundle
     *
     * @param string $directory
     * @return Ambigous <multitype:string , multitype:>
     */
    protected function getFiles($directory)
    {

        $result = array();

        $directoryHandler = opendir($directory);
        while ($file = readdir($directoryHandler)) {
            if (!in_array($file, array('.', '..'))) {
                if (is_dir($directory . '/' . $file)) {
                    $result = array_merge($result, $this->getFiles($directory . '/' . $file));
                } else {
                    // Get only php and twig files
                    if (preg_match('/(.*)\.php|(.*)\.twig$/si', $file)) {
                        $result[] = $directory . '/' . $file;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Processing source files for translation tags
     * @param string $filename
     * @return array
     */
    protected function processingSourceFile($filename)
    {
        $content = file_get_contents($filename);
        $result = array();

        $patterns = array(
            // $this->translate('id'[, arguments])
            '/\s*->\s*translate\s*\(\s*\'(.*)\'\s*(?:.*)\)/Us',
            // {{ value|trans() }}
            '/{{\s*[\'"]{1}(.*)[\'"]{1}\s*\|\s*trans\s*\(*\s*\)*\s*}}/U',
            // $this->get('translator')->trans('id'[, arguments])
            '/\s*->\s*trans\s*\(\s*\'(.*)\'.*(?:.*)\)/Us',
            // trans('id'[, arguments[, domain [, locale]]])
            '/\s*trans\s*\(\s*\'(.*)\'.*(?:.*)\)/Us',
            // {% trans %} id {% endtrans %}
            '/{%\s*trans\s*%}\s*(.*)\s*{%\s*endtrans\s*%}/U',
            // message='value'
            '/\s+message\s*=\s*[\'"]{1}(.*)[\'"]{1}/U'
        );

        foreach ($patterns as $pattern) {
            $result = $this->searchForIds($content, $pattern, $result);
        }

        return $result;
    }

    /**
     *
     * @param string $content
     * @param string $pattern
     * @param array $result
     * @return array
     */
    private function searchForIds($content, $pattern, array $result)
    {

        preg_match_all($pattern, $content, $values);

        $values = $values[1];

        if (count($values) > 0) {
            foreach ($values as $value) {
                $result[] = $value;
            }
        }

        return array_unique($result);
    }

}
