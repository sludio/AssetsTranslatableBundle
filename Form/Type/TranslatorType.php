<?php

namespace Assets\TranslatableBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Assets\TranslatableBundle\Helpers\TranslatableManager as TranslatableManager;

class TranslatorType extends AbstractType {
    protected $translatablemanager;
    private $locales;
    private $userLocale;
    private $translator;

    public function __construct($localeCodes ,TranslatableManager $translatableManager, TranslatorInterface $translator) {
        $this->translatablemanager = $translatableManager;
        $this->translator = $translator;
        $this->locales = $localeCodes;
        $this->userLocale = $this->translator->getLocale();
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $this->checkOptions($options);

        $fieldName = $builder->getName();
        $className = $options['translation_data_class'];
        $id = $options['object_id'];
        $locales = $options['locales'];
        $userLocale = $this->userLocale;
		$fieldType = $options['fieldtype'];
		$class = $options['class'];
        $new = $options['new'];
        $required = $options['required'];

        // fetch data for each locale on this field of the object
        if($new){
            $translations = $this->translatablemanager->getNewTranslatedFields($className, $fieldName, $locales, $userLocale);
        } else {
            $translations = $this->translatablemanager->getTranslatedFields($className, $fieldName, $id, $locales, $userLocale);
        }

        // 'populate' fields by *hook on form generation
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($fieldName, $locales, $translations, $fieldType, $class, $required) {
            $form = $event->getForm();
            foreach($locales as $locale) {
                $data = (array_key_exists($locale, $translations) && array_key_exists($fieldName, $translations[$locale])) ? $translations[$locale][$fieldName] : NULL;
                $form->add($locale, $fieldType, ['label' => false, 'data' => $data, 'required' => $required, 'attr' => ['class' => $class]]);
            }

            // extra field for twig rendering
            $form->add('currentFieldName', 'hidden', array('data' => $fieldName));
        });

        if(!$new){
        // submit
            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($fieldName, $className, $id, $locales, $userLocale) {
                $form = $event->getForm();
                $this->translatablemanager->persistTranslations($form, $className, $fieldName, $id, $locales, $userLocale);
            });
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options) {
        // pass some variables for field rendering
        $view->vars['locales'] = $options['locales'];
        $view->vars['currentlocale'] = $this->userLocale;
        $view->vars['tranlatedtablocales'] = $this->getTabTranslations();
    }

    public function getName() {
        return 'translations';
    }

    private function getTabTranslations() {
        $translatedLocaleCodes = array();
        foreach($this->locales as $locale) {
            $translatedLocaleCodes[$locale] = $this->getTranslatedLocalCode($locale);
        }

        return $translatedLocaleCodes;
    }

    private function getTranslatedLocalCode($localeCode) {
        return \Locale::getDisplayLanguage($localeCode, $this->userLocale);
    }

    private function checkOptions($options) {
        $condition_dataclass_empty = ($options['translation_data_class'] === "");
        $condition_id_null = ($options['object_id'] === null && !$options['new']);
        $condition_locales_invalidarray = (!is_array($options['locales']) || empty($options['locales']));

        if($condition_dataclass_empty || $condition_id_null || $condition_locales_invalidarray) {
            throw new \Exception('An Error Ocurred');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            array(
                'locales' => $this->locales,
                'translation_data_class' => "",
                'object_id' => null,
                'mapped' => false,
                'required' => false,
                'by_reference' => false,
                'fieldtype' => 'text',
                'class' => '',
                'new' => false
            )
        );
    }

}
