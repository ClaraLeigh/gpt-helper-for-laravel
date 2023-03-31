<?php

namespace GptHelperForLaravel\Support;

use Illuminate\Support\Facades\File;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node\Stmt\Class_;
use ReflectionClass;

class SummarizeFile
{
    private PrettyPrinter\Standard $prettyPrinter;
    private DocBlockFactory $docBlockFactory;

    public function __construct()
    {
        $this->prettyPrinter = new PrettyPrinter\Standard();
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    public function run(string $filePath): string
    {
        // Validate the file exists
        if (!File::exists($filePath)) {
            throw new \Exception('The file ' . $filePath . ' does not exist.');
        }

        $code = File::get($filePath);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $statements = $parser->parse($code);

        $summary = '';

        foreach ($statements as $statement) {
            if ($statement instanceof Node\Stmt\Namespace_) {
                foreach ($statement->stmts as $namespacedStmt) {
                    if ($namespacedStmt instanceof Class_) {
                        $summary .= $this->summarizeClass($namespacedStmt);
                    }
                }
            } elseif ($statement instanceof Class_) {
                $summary .= $this->summarizeClass($statement);
            }
        }

        return $summary;
    }

    /**
     * @param  string  $summary
     * @param  Class_  $class
     *
     * @return string
     */
    protected function getProperties(string $summary, Class_ $class): string
    {
        $summary .= "- Properties:".PHP_EOL;
        // Get the properties of the class
        foreach ($class->getProperties() as $property) {
            $propertyFlags = $this->flagsToString($property->flags);
            $summary       .= "  - (".$propertyFlags.") ".$this->prettyPrinter->prettyPrint([$property]);

            // Add the docblock summary, if it exists
            if ($property->getDocComment() !== null) {
                $propertyDocBlock = $this->docBlockFactory->create($property->getDocComment()->getText());
                $summary          .= " - ".$propertyDocBlock->getSummary();
            }
            $summary .= PHP_EOL;
        }

        return $summary;
    }


    private function validateFileExists(string $filePath): void
    {
        if (!File::exists($filePath)) {
            throw new \Exception('The file ' . $filePath . ' does not exist.');
        }
    }

    /**
     * Summarize a class
     *
     * @param  Class_  $class
     * @return string
     */
    private function summarizeClass(Class_ $class): string
    {
        $summary = "Class: " . $class->name . PHP_EOL;

        if (count($class->getProperties())) {
            $summary = $this->getProperties($summary, $class);
        }

        // Get the methods of the class
        $summary .= "- Methods:" . PHP_EOL;
        foreach ($class->getMethods() as $method) {
            $summary .= $this->summarizeMethod($method);
        }

        return $summary;
    }

    /**
     * Summarize a method
     *
     * @param  Node\Stmt\ClassMethod  $method
     * @return string
     */
    private function summarizeMethod(Node\Stmt\ClassMethod $method): string
    {
        $methodFlags    = $this->flagsToString($method->flags);
        $summary = "  - (" . $methodFlags . ") " . $method->name . "(" . implode(', ', array_map(function ($param) {
                return $this->prettyPrinter->prettyPrint([$param]);
            }, $method->params)) . "): " . ($method->returnType ?: 'mixed');

        // Add the method summary if it exists
        if ($method->getDocComment() !== null) {
            $methodDocBlock = $this->docBlockFactory->create($method->getDocComment()->getText());
            $summary .= " - ".$methodDocBlock->getSummary();
        }

        $summary .= PHP_EOL;

        $calledClassesAndMethods = $this->getCalledClassesAndMethods($method);
        if (!empty($calledClassesAndMethods)) {
            $summary .= "    - See these related functions for more information, these are provided for context only: " . implode(', ', $calledClassesAndMethods) . PHP_EOL;
        }

        return $summary;
    }


    /**
     * Get the classes and methods that are called in a method
     *
     * @param  Node\Stmt\ClassMethod  $method
     * @return array
     */
    private function getCalledClassesAndMethods(Node\Stmt\ClassMethod $method): array
    {
        $calledClassesAndMethods = [];
        $nodeFinder = new NodeFinder;
        $nodes = $nodeFinder->find($method->stmts, function (Node $node) {
            return $node instanceof MethodCall || $node instanceof StaticCall;
        });

        foreach ($nodes as $node) {
            if ($node instanceof MethodCall) {
                $methodName = $node->name instanceof Identifier ? $node->name->toString() : 'dynamic_method';
                $calledClassesAndMethods[] = $this->prettyPrinter->prettyPrintExpr($node->var) . '->' . $methodName . '()';
            } elseif ($node instanceof StaticCall) {
                $className = $this->prettyPrinter->prettyPrint([$node->class]);
                $methodName = $node->name instanceof Identifier ? $node->name->toString() : 'dynamic_method';
                $calledClassesAndMethods[] = $className . '::' . $methodName . '()';
            }
        }

        return $calledClassesAndMethods;
    }

    /**
     * Convert the flags to a string
     *
     * @param  int  $flags
     * @return string
     */

    private function flagsToString(int $flags): string
    {
        $result = [];

        if ($flags & Class_::MODIFIER_PUBLIC) {
            $result[] = 'public';
        } elseif ($flags & Class_::MODIFIER_PROTECTED) {
            $result[] = 'protected';
        } elseif ($flags & Class_::MODIFIER_PRIVATE) {
            $result[] = 'private';
        }

        if ($flags & Class_::MODIFIER_STATIC) {
            $result[] = 'static';
        }

        return implode(' ', $result);
    }
}