<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Migration\Code\Processor\Mage\MageFunction;

use Magento\Migration\Code\Processor\Mage\MageFunctionInterface;
class HelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Migration\Code\Processor\Mage\MageFunction\Helper
     */
    protected $obj;

    /**
     * @var \Magento\Migration\Mapping\ClassMapping|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $classMapperMock;

    /**
     * @var \Magento\Migration\Mapping\Alias|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $aliasMapperMock;

    /**
     * @var \Magento\Migration\Logger\Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \Magento\Migration\Code\Processor\TokenHelper
     */
    protected $tokenHelper;

    /**
     * @var \Magento\Migration\Code\Processor\Mage\MageFunction\ArgumentFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $argumentFactoryMock;

    public function setUp()
    {
        $this->loggerMock = $this->getMock('\Magento\Migration\Logger\Logger');

        $this->classMapperMock = $this->getMockBuilder(
            '\Magento\Migration\Mapping\ClassMapping'
        )->disableOriginalConstructor()
            ->getMock();
        $this->aliasMapperMock = $this->getMockBuilder(
            '\Magento\Migration\Mapping\Alias'
        )->disableOriginalConstructor()
            ->getMock();

        $this->tokenHelper = $this->setupTokenHelper($this->loggerMock);

        $this->argumentFactoryMock = $this->getMockBuilder(
            '\Magento\Migration\Code\Processor\Mage\MageFunction\ArgumentFactory'
        )->setMethods(['create'])
            ->getMock();

        $this->obj = new \Magento\Migration\Code\Processor\Mage\MageFunction\Helper(
            $this->classMapperMock,
            $this->aliasMapperMock,
            $this->loggerMock,
            $this->tokenHelper,
            $this->argumentFactoryMock
        );
    }

    /**
     * @dataProvider helperDataProvider
     */
    public function testHelper(
        $inputFile,
        $index,
        $attrs,
        $mappedHelperClass,
        $expectedFile
    ) {
        $file = __DIR__ . '/_files/' . $inputFile;
        $fileContent = file_get_contents($file);

        $tokens = token_get_all($fileContent);

        $this->obj->setContext($tokens, $index);

        $this->aliasMapperMock->expects($this->any())
            ->method('mapAlias')
            ->with('tax', 'helper')
            ->willReturn('Mage_Tax_Helper');

        $this->classMapperMock->expects($this->any())
            ->method('mapM1Class')
            ->willReturn($mappedHelperClass);

        $this->assertEquals($attrs['start_index'], $this->obj->getStartIndex());
        $this->assertEquals($attrs['end_index'], $this->obj->getEndIndex());
        $this->assertEquals($attrs['method'], $this->obj->getMethod());
        $this->assertEquals($attrs['type'], $this->obj->getType());
        $this->assertEquals($attrs['class'], $this->obj->getClass());
        $this->assertEquals($attrs['di_variable_name'], $this->obj->getDiVariableName());
        $this->assertEquals($attrs['di_variable_class'], $this->obj->getDiClass());

        $this->obj->convertToM2();

        $updatedContent = $this->tokenHelper->reconstructContent($tokens);

        $expectedFile = __DIR__ . '/_files/' . $expectedFile;
        $expected = file_get_contents($expectedFile);
        $this->assertEquals($expected, $updatedContent);
    }

    public function helperDataProvider()
    {
        $data = [
            'helper_translation' => [
                'input' => 'helper_translation',
                'index' => 31,
                'attr' => [
                    'start_index' => 31,
                    'end_index' => 37,
                    'method' => '__',
                    'class' => null,
                    'type' => MageFunctionInterface::MAGE_HELPER,
                    'di_variable_name' => null,
                    'di_variable_class' => null,

                ],
                'mapped_helper_class' => '\Magento\Tax\Helper\Data',
                'expected' => 'helper_translation_expected',
            ],
            'helper_not_translation' => [
                'input' => 'helper_not_translation',
                'index' => 31,
                'attr' => [
                    'start_index' => 31,
                    'end_index' => 36,
                    'method' => 'doSomething',
                    'class' => '\Magento\Tax\Helper\Data',
                    'type' => MageFunctionInterface::MAGE_HELPER,
                    'di_variable_name' => 'taxHelper',
                    'di_variable_class' => '\Magento\Tax\Helper\Data',

                ],
                'mapped_helper_class' => '\Magento\Tax\Helper\Data',
                'expected' => 'helper_not_translation_expected',
            ],
            'helper_variable' => [
                'input' => 'helper_variable',
                'index' => 31,
                'attr' => [
                    'start_index' => 31,
                    'end_index' => null,
                    'method' => null,
                    'class' => null,
                    'type' => MageFunctionInterface::MAGE_HELPER,
                    'di_variable_name' => null,
                    'di_variable_class' => null,

                ],
                'mapped_helper_class' => null,
                'expected' => 'helper_variable_expected',
            ],
            'helper_specific_helper' => [
                'input' => 'helper_specific_helper',
                'index' => 31,
                'attr' => [
                    'start_index' => 31,
                    'end_index' => 36,
                    'method' => 'doSomething',
                    'class' => '\Magento\Tax\Helper\Config',
                    'type' => MageFunctionInterface::MAGE_HELPER,
                    'di_variable_name' => 'taxConfigHelper',
                    'di_variable_class' => '\Magento\Tax\Helper\Config',

                ],
                'mapped_helper_class' => '\Magento\Tax\Helper\Config',
                'expected' => 'helper_specific_helper_expected',
            ],
            'helper_not_mapped' => [
                'input' => 'helper_not_mapped',
                'index' => 31,
                'attr' => [
                    'start_index' => 31,
                    'end_index' => 36,
                    'method' => 'doSomething',
                    'class' => '\Mage\Tax\Helper\Config',
                    'type' => MageFunctionInterface::MAGE_HELPER,
                    'di_variable_name' => 'taxConfigHelper',
                    'di_variable_class' => '\Mage\Tax\Helper\Config',

                ],
                'mapped_helper_class' => null,
                'expected' => 'helper_not_mapped_expected',
            ],
        ];
        return $data;
    }

    /**
     * @param \Magento\Migration\Logger\Logger $loggerMock
     * @return \Magento\Migration\Code\Processor\TokenHelper
     */
    public function setupTokenHelper(\Magento\Migration\Logger\Logger $loggerMock)
    {
        $argumentFactoryMock = $this->getMockBuilder(
            '\Magento\Migration\Code\Processor\Mage\MageFunction\ArgumentFactory'
        )->setMethods(['create'])
            ->getMock();
        $argumentFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                function () {
                    return new \Magento\Migration\Code\Processor\Mage\MageFunction\Argument();
                }
            );
        $tokenFactoryMock = $this->getMockBuilder(
            '\Magento\Migration\Code\Processor\TokenArgumentFactory'
        )->setMethods(['create'])
            ->getMock();
        $tokenFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                function () {
                    return new \Magento\Migration\Code\Processor\TokenArgument();
                }
            );


        $tokenCollectionFactoryMock = $this->getMockBuilder(
            '\Magento\Migration\Code\Processor\TokenArgumentCollectionFactory'
        )->setMethods(['create'])
            ->getMock();
        $tokenCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                function () {
                    return new \Magento\Migration\Code\Processor\TokenArgumentCollection();
                }
            );

        $callCollectionFactoryMock = $this->getMockBuilder(
            '\Magento\Migration\Code\Processor\CallArgumentCollectionFactory'
        )->setMethods(['create'])
            ->getMock();
        $callCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                function () {
                    return new \Magento\Migration\Code\Processor\CallArgumentCollection();
                }
            );

        $tokenHelper = new \Magento\Migration\Code\Processor\TokenHelper(
            $loggerMock,
            $argumentFactoryMock,
            $tokenFactoryMock,
            $tokenCollectionFactoryMock,
            $callCollectionFactoryMock
        );

        return $tokenHelper;
    }
}
