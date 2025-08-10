<?php

namespace App\Filament\Admin\Pages;

use Exception;
use Filament\Pages\Page;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class About extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static string $view = 'filament.admin.pages.about';

    protected static ?string $navigationLabel = 'About';

    protected static ?string $title = 'About OpenGRC';

    protected static ?int $navigationSort = 1000;

    protected static ?string $navigationGroup = 'System';

    public array $version = [];
    public array $license = [];

    public function mount(): void
    {
        $this->version = $this->getAppVersion();
        $this->license = $this->getLicenseInfo();
    }

    protected function getAppVersion(): array
    {
        $gitInfo = $this->getGitInfo();
        
        return [
            'tag' => $gitInfo['tag'] ?? 'No tags found',
            'commit' => $gitInfo['commit'] ?? 'Unknown',
            'branch' => $gitInfo['branch'] ?? 'Unknown',
            'date' => $gitInfo['date'] ?? 'Unknown',
            'display' => $gitInfo['display'] ?? 'Development',
        ];
    }

    protected function getGitInfo(): array
    {
        $gitDir = base_path('.git');
        
        if (!is_dir($gitDir)) {
            return [
                'display' => 'No Git repository',
                'tag' => null,
                'commit' => 'Not available',
                'branch' => 'Not available',
                'date' => 'Not available',
            ];
        }

        // Try to read Git information directly from files first
        $gitInfo = $this->readGitFromFiles($gitDir);
        
        // If file reading fails, try shell_exec as fallback
        if ($gitInfo['commit'] === 'Unknown') {
            $gitInfo = $this->readGitFromShell();
        }

        return $gitInfo;
    }

    protected function readGitFromFiles(string $gitDir): array
    {
        $commit = 'Unknown';
        $branch = 'Unknown';
        $date = 'Unknown';
        $tag = null;

        try {
            // Read current HEAD commit
            $headFile = $gitDir . '/HEAD';
            if (file_exists($headFile)) {
                $headContent = trim(file_get_contents($headFile));
                
                if (str_starts_with($headContent, 'ref: ')) {
                    // HEAD points to a branch
                    $branchRef = substr($headContent, 5);
                    $branch = basename($branchRef);
                    
                    $refFile = $gitDir . '/' . $branchRef;
                    if (file_exists($refFile)) {
                        $fullCommit = trim(file_get_contents($refFile));
                        $commit = substr($fullCommit, 0, 7);
                    }
                } else {
                    // Detached HEAD
                    $commit = substr($headContent, 0, 7);
                    $branch = 'HEAD';
                }
            }

            // Try to find tags
            $tagsDir = $gitDir . '/refs/tags';
            if (is_dir($tagsDir)) {
                $tags = [];
                try {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tagsDir, RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $tagName = str_replace($tagsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                            $tags[] = $tagName;
                        }
                    }
                    
                    if (!empty($tags)) {
                        // Sort tags and get the latest (this is a simple approach)
                        sort($tags);
                        $tag = end($tags);
                    }
                } catch (Exception $e) {
                    // If directory iteration fails, continue without tags
                }
            }

        } catch (Exception $e) {
            // Ignore errors and continue with fallback values
        }

        // Determine display version
        if ($tag) {
            $display = $tag;
        } elseif ($commit !== 'Unknown') {
            $display = "dev-{$commit}";
        } else {
            $display = 'Development';
        }

        return [
            'tag' => $tag,
            'commit' => $commit,
            'branch' => $branch,
            'date' => $date,
            'display' => $display,
        ];
    }

    protected function readGitFromShell(): array
    {
        $basePath = base_path();
        
        // Test if shell_exec is available
        if (!function_exists('shell_exec') || shell_exec('echo test') === null) {
            return [
                'tag' => null,
                'commit' => 'Shell exec disabled',
                'branch' => 'Shell exec disabled',
                'date' => 'Shell exec disabled',
                'display' => 'Development (shell_exec disabled)',
            ];
        }

        // Get current commit hash (short)
        $commitHash = trim((string) shell_exec("cd '$basePath' && git rev-parse --short HEAD 2>/dev/null"));
        
        // Get current branch
        $branch = trim((string) shell_exec("cd '$basePath' && git rev-parse --abbrev-ref HEAD 2>/dev/null"));
        
        // Get commit date
        $commitDate = trim((string) shell_exec("cd '$basePath' && git log -1 --format=%cd --date=short 2>/dev/null"));
        
        // Get the latest tag
        $latestTag = trim((string) shell_exec("cd '$basePath' && git describe --tags --abbrev=0 2>/dev/null"));
        $latestTag = empty($latestTag) ? null : $latestTag;
        
        // Determine display version
        if ($latestTag) {
            $display = $latestTag;
        } elseif ($commitHash) {
            $display = "dev-{$commitHash}";
        } else {
            $display = 'Development';
        }
        
        return [
            'tag' => $latestTag,
            'commit' => $commitHash ?: 'Unknown',
            'branch' => $branch ?: 'Unknown',
            'date' => $commitDate ?: 'Unknown',
            'display' => $display,
        ];
    }

    protected function getLicenseInfo(): array
    {
        return [
            'type' => 'Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International',
            'short_type' => 'CC BY-NC-SA 4.0',
            'url' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
            'text' => $this->getOpenGRCLicenseText(),
        ];
    }

    protected function getOpenGRCLicenseText(): string
    {
        return 'OpenGRC is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License.

To view a copy of this license, visit:
https://creativecommons.org/licenses/by-nc-sa/4.0/

EXCEPTIONS:

• Code Commits prior to April 14, 2025 are MIT Licensed.

• Commercial use is permitted as long as resale of the OpenGRC code is not involved in any way. 
  In other words, you may use this for your own company to help you in your own GRC endeavors.

• Hosting of this software for customers - regardless of compensation - is not permitted.

SUMMARY:

Under this license, you are free to:
• Share — copy and redistribute the material in any medium or format
• Adapt — remix, transform, and build upon the material

Under the following terms:
• Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made.
• NonCommercial — You may not use the material for commercial purposes (except as noted in exceptions above).
• ShareAlike — If you remix, transform, or build upon the material, you must distribute your contributions under the same license.

For more details about this license, visit the Creative Commons website at the URL above.';
    }
}