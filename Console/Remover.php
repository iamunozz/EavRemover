<?php
/*
 * Copyright © Ignacio Muñoz © All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Iamunozz\EavRemover\Console;


use Magento\Framework\Validation\ValidationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Config as EavConfig;
use Iamunozz\EavRemover\Model\AttributesSet;

/**
 * Class Remover
 */
class Remover extends Command
{
    const TYPES = ['varchar', 'int', 'decimal', 'text', 'datetime'];
    const ATTRIBUTE_CODE = 'code';
    const ATTRIBUTE_TYPE = 'type';
    const EAV_PRODUCT_ATTRIBUTE = \Magento\Catalog\Model\Product::ENTITY;
    const EAV_CUSTOMER_ATTRIBUTE = \Magento\Customer\Model\Customer::ENTITY;
    const EAV_ATTRIBUTE_TYPE_OPTIONS = ['0' => 'catalog_product', '1' => 'customer'];

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    protected $setup;

    /**
     * @var EavConfig
     */
    private $_eavConfig;

    /**
     * @var AttributesSet
     */
    protected $setOfAttributes;

     /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $type;



    /**
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $setup
     * @param EavConfig $eavConfig
     * @param string|null $name
     * @param AttributesSet $setOfAttributes
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $setup,
        EavConfig $eavConfig,
        AttributesSet $setOfAttributes,
        string $name = null,
    ) {
        parent::__construct($name);
        $this->eavSetupFactory = $eavSetupFactory;
        $this->setup = $setup;
        $this->_eavConfig = $eavConfig;
        $this->setOfAttributes = $setOfAttributes;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = [
			new InputOption(
				self::ATTRIBUTE_TYPE,
				null,
				InputOption::VALUE_REQUIRED,
				"Attribute type to be removed, can be type of 'customer' or 'catalog_product'"
            ),
            new InputOption(
				self::ATTRIBUTE_CODE,
				null,
				InputOption::VALUE_REQUIRED,
				'Attribute code to be removed'
            ),
		];

        $this->setName('eav:remover');
        $this->setDescription('Remove eav attribute in interaction mode.');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * Remove eav attribute in interaction mode.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        if (!$input->getOption(self::ATTRIBUTE_TYPE)) {
            $question = new ChoiceQuestion("<question>Select attribute type: </question>", self::EAV_ATTRIBUTE_TYPE_OPTIONS, '');
            $this->addNotEmptyValidator($question);
            $this->addOptionChoisedValidator($question);

            $input->setOption(
                self::ATTRIBUTE_TYPE,
                $questionHelper->ask($input, $output, $question)
            );

            $this->type = $input->getOption(self::ATTRIBUTE_TYPE);
        }

        if (!$input->getOption(self::ATTRIBUTE_CODE)) {
            $question = new Question('<question>Attribute code: </question>', '');
            $this->addNotEmptyValidator($question);
            $this->addAttributeExistValidator($question);

            $input->setOption(
                self::ATTRIBUTE_CODE,
                $questionHelper->ask($input, $output, $question)
            );
        }
    }

    /**
     * Add not empty validator.
     *
     * @param \Symfony\Component\Console\Question\Question $question
     * @return void
     */
    private function addNotEmptyValidator(Question $question)
    {
        $question->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new ValidationException(__('The value cannot be empty'));
            }

            return $value;
        });
    }

     /**
     * Add attribute type validator.
     *
     * @param \Symfony\Component\Console\Question\Question $question
     * @return void
     */
    private function addOptionChoisedValidator(Question $question)
    {
        $question->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new ValidationException(__('The value cannot be empty'));
            }

            if (!array_key_exists($value, self::EAV_ATTRIBUTE_TYPE_OPTIONS)) {
                throw new ValidationException(__('The value must be one of the options'));
            }

            return self::EAV_ATTRIBUTE_TYPE_OPTIONS[$value];
        });
    }

     /**
     * Add attribute type validator.
     *
     * @param \Symfony\Component\Console\Question\Question $question
     * @return void
     */
    private function addAttributeExistValidator(Question $question)
    {
        $question->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new ValidationException(__('The value cannot be empty'));
            }

            if (!$this->type){
                throw new ValidationException(__('The attribute type is not defined'));
            }

            if($this->type === self::EAV_PRODUCT_ATTRIBUTE){
                $attributesSetIds =  $this->setOfAttributes->getAllAttributeSetIds();
                if($this->setOfAttributes->existAttributeOnSet($attributesSetIds, $value)){
                    throw new ValidationException(__('The attribute exist on any attribute set'));
                }
            }

            $attributeCodes = array_keys($this->_eavConfig->getEntityAttributes($this->type));
            if (!in_array($value, $attributeCodes)) {
                throw new ValidationException(__('The attribute does not exist'));
            }

            return $value;
        });
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $attributeType = $input->getOption(self::ATTRIBUTE_TYPE);
            $attributeCode = $input->getOption(self::ATTRIBUTE_CODE);

            $output->writeln("<info>Deleting {$attributeType} eav attribute.</info>");

            if($attributeType == self::EAV_PRODUCT_ATTRIBUTE){
                $resultTime = $this->removeEav($this->setup, $attributeCode, self::EAV_PRODUCT_ATTRIBUTE);
            } else if($attributeType == self::EAV_CUSTOMER_ATTRIBUTE){
                $resultTime = $this->removeEav($this->setup, $attributeCode, self::EAV_CUSTOMER_ATTRIBUTE);
            } else {
                $output->writeln("<error>Attribute type {$attributeType} not found.</error>");
                return 1;
            }

        } catch (\Exception $th) {
            $output->writeln("<error>{$th->getMessage()}</error>");
            return 1;
        }

        $output->writeln(
            __("<info>Attribute {$attributeCode} has been remove successfully in %time</info>", ['time' => gmdate('H:i:s', (int) $resultTime ?? 0)])
        );

        return 0;
    }

    /**
     * Remove eav attribute function
     *
     * @param ModuleDataSetupInterface $setup
     * @param string $attributeCode
     * @param string $attributeType
     * @return int|float
     */
    public function removeEav(ModuleDataSetupInterface $setup, $attributeCode, $attributeType)
    {
        $startTime = microtime(true);
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->removeAttribute(
            $attributeType,
            $attributeCode
        );
        $resultTime = microtime(true) - $startTime;
        return $resultTime;
    }
}
