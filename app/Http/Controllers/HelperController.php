<?php

namespace App\Http\Controllers;

class HelperController extends Controller
{
    /**
     * Converts each line of the given text into a paragraph element.
     *
     * This function splits the input text into individual lines and wraps each line
     * in a paragraph (`<p>`) tag. If a string of classes is provided, it adds them
     * as the class attribute of the paragraph tags.
     *
     * @param  string  $text  The text to be converted into paragraphs.
     * @param  string|null  $classes  Optional string of classes to be added to each paragraph tag.
     * @return string The converted text with each line wrapped in a paragraph tag.
     *                If classes are provided, each paragraph tag will include them.
     */
    public static function linesToParagraphs($text, $classes = null): string
    {
        $lines = explode("\n", trim($text));
        $paragraphs = array_map(function ($line) use ($classes) {
            $classAttribute = $classes ? " class='".e($classes)."'" : '';

            return '<p'.$classAttribute.'>'.e($line).'</p>';
        }, $lines);

        return implode('', $paragraphs);
    }
}
