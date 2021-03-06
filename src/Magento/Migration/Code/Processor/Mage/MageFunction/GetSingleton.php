<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Migration\Code\Processor\Mage\MageFunction;

use Magento\Migration\Code\Processor\Mage\MageFunctionInterface;

class GetSingleton extends AbstractFunction implements \Magento\Migration\Code\Processor\Mage\MageFunctionInterface
{
    /**
     * @var string
     */
    protected $singletonClass = null;

    /**
     * @var string
     */
    protected $methodName = null;

    /**
     * @var int
     */
    protected $endIndex = null;

    /**
     * @var string
     */
    protected $diVariableName = null;

    /**
     * @var bool
     */
    protected $parsed = false;

    /**
     * @return $this
     */
    private function parse()
    {
        $this->parsed = true;
        $argument = $this->getMageCallFirstArgument($this->index);
        if (is_array($argument) && $argument[0] != T_VARIABLE) {
            $this->singletonClass = $this->getSingletonClass($argument[1]);
            if (!$this->singletonClass) {
                return $this;
            }
            if ($this->singletonClass == "obsolete") {
                $this->singletonClass = null;
                $this->logger->warn(
                    'Obsolete model not converted at ' . $this->tokens[$this->index][2] . ' ' . $argument[1]
                );
                return $this;
            }
        } else {
            if (!is_array($argument)) {
                $this->logger->warn('Unexpected token for getSingleton call at ' . $this->tokens[$this->index][2]);
            } else {
                $this->logger->warn(
                    'Variable inside a Mage::getSingleton call not converted: ' . $this->tokens[$this->index][2]
                );
            }
            return $this;
        }

        $this->diVariableName = $this->generateVariableName($this->singletonClass);
        $this->methodName = $this->getSingletonMethod();

        $this->endIndex = $this->tokenHelper->skipMethodCall($this->tokens, $this->index) - 1;
        return $this;
    }

    /**
     * @param string $m1
     * @return null|string
     */
    protected function getSingletonClass($m1)
    {
        $m1 = trim(trim($m1, '\''), '\"');

        if (strpos($m1, '/') === false) {
            //the argument is full class name
            $m1ClassName = $m1;
        } else {
            $parts = explode('/', $m1);
            $className = $this->aliasMapper->mapAlias($parts[0], 'model');
            if ($className == null) {
                $this->logger->warn("Model alias in Mage::getSingleton call is not mapped: " . $parts[0]);
                return null;
            }
            $part2 = str_replace(' ', '_', ucwords(implode(' ', explode('_', $parts[1]))));
            $m1ClassName = $className . '_' . $part2;
        }

        $m2ClassName = $this->classMapper->mapM1Class($m1ClassName);
        if (!$m2ClassName) {
            $m2ClassName = '\\' . str_replace('_', '\\', $m1ClassName);
        }

        return $m2ClassName;
    }

    /**
     * @param string $helperClassName
     * @return string
     */
    protected function generateVariableName($helperClassName)
    {
        $parts = explode('\\', trim($helperClassName, '\\'));

        $parts[0] = '';
        $parts[2] = '';
        $variableNameParts = $parts;

        $variableName = lcfirst(str_replace(' ', '', ucwords(implode(' ', $variableNameParts))));
        return $variableName;
    }

    /**
     * The format is Mage::getSingleton('module/helpername')->methodName
     *
     * @return string
     */
    protected function getSingletonMethod()
    {
        $nextIndex = $this->tokenHelper->skipMethodCall($this->tokens, $this->index);
        $nextIndex = $this->tokenHelper->getNextIndexOfTokenType($this->tokens, $nextIndex, T_STRING);
        return $this->tokens[$nextIndex][1];

    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return MageFunctionInterface::MAGE_GET_SINGLETON;
    }

    /**
     * @inheritdoc
     */
    public function getClass()
    {
        if (!$this->parsed) {
            $this->parse();
        }

        return $this->singletonClass;
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        if (!$this->parsed) {
            $this->parse();
        }
        return $this->methodName;
    }

    /**
     * @inheritdoc
     */
    public function getStartIndex()
    {
        return $this->index;
    }

    /**
     * @inheritdoc
     */
    public function getEndIndex()
    {
        if (!$this->parsed) {
            $this->parse();
        }
        return $this->endIndex;
    }

    /**
     * @inheritdoc
     */
    public function convertToM2()
    {
        if (!$this->parsed) {
            $this->parse();
        }

        if ($this->methodName == null || $this->singletonClass == null) {
            return $this;
        }

        $indexOfMethodCall = $this->tokenHelper->skipMethodCall($this->tokens, $this->index);

        $this->tokenHelper->eraseTokens($this->tokens, $this->index, $indexOfMethodCall - 1);

        $this->tokens[$this->index] = '$this->' . $this->diVariableName;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDiVariableName()
    {
        if (!$this->parsed) {
            $this->parse();
        }
        return $this->diVariableName;
    }

    /**
     * @inheritdoc
     */
    public function getDiClass()
    {
        return $this->getClass();
    }
}
