<?php

namespace Acquia\Cli\Output\Spinner;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 *
 */
class Spinner {
  private const CHARS = ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇'];
  private const COLORS = [
    196,
    196,
    202,
    202,
    208,
    208,
    214,
    214,
    220,
    220,
    226,
    226,
    190,
    190,
    154,
    154,
    118,
    118,
    82,
    82,
    46,
    46,
    47,
    47,
    48,
    48,
    49,
    49,
    50,
    50,
    51,
    51,
    45,
    45,
    39,
    39,
    33,
    33,
    27,
    27,
    56,
    56,
    57,
    57,
    93,
    93,
    129,
    129,
    165,
    165,
    201,
    201,
    200,
    200,
    199,
    199,
    198,
    198,
    197,
    197,
  ];

  private $currentCharIdx = 0;
  /**
   * @var int*/
  private $currentColorIdx = 0;
  /**
   * @var int*/
  private $colorCount;
  /**
   * @var \Symfony\Component\Console\Helper\ProgressBar
   */
  private $progressBar;
  /**
   * @var int*/
  private $colorLevel;

  /**
   * @var \Symfony\Component\Console\Output\ConsoleSectionOutput*/
  private $section;
  /**
   * @var string
   */
  private $indentString;

  /**
   *
   */
  public function __construct(ConsoleOutput $output, $indent = 0, $colorLevel = Color::COLOR_256) {
    $this->section = $output->section();
    $this->colorLevel = $colorLevel;
    $this->colorCount = count(self::COLORS);
    $this->indentString = str_repeat(' ', $indent);

    // Create progress bar.
    $this->progressBar = new ProgressBar($this->section);
    $this->progressBar->setBarCharacter('<info>✔</info>');
    $this->progressBar->setProgressCharacter('⌛');
    $this->progressBar->setEmptyBarCharacter('⌛');
    $this->progressBar->setFormat($this->indentString . '%bar% %message%');
    $this->progressBar->setBarWidth(1);
    $this->progressBar->setRedrawFrequency($this->interval());
  }

  /**
   *
   */
  public function start(): void {
    $this->progressBar->start();
  }

  /**
   *
   */
  public function advance(): void {
    ++$this->currentCharIdx;
    ++$this->currentColorIdx;
    $char = $this->getSpinnerCharacter();
    $this->progressBar->setProgressCharacter($char);
    $this->progressBar->advance();
  }

  /**
   *
   */
  protected function getSpinnerCharacter(): ?string {
    if ($this->currentColorIdx === $this->colorCount) {
      $this->currentColorIdx = 0;
    }
    $char = self::CHARS[$this->currentCharIdx % 8];
    $color = self::COLORS[$this->currentColorIdx];

    if (Color::COLOR_256 === $this->colorLevel) {
      return "\033[38;5;{$color}m{$char}\033[0m";
    }
    if (Color::COLOR_16 === $this->colorLevel) {
      return "\033[96m{$char}\033[0m";
    }
  }

  /**
   *
   */
  public function setMessage(string $message): void {
    $this->progressBar->setMessage($message);
  }

  /**
   *
   */
  public function finish(): void {
    $this->section->overwrite($this->indentString . '<info>✔</info> ' . $this->progressBar->getMessage());
    $this->progressBar->finish();
  }

  /**
   *
   */
  public function fail(): void {
    $this->section->overwrite($this->indentString . '❌' . $this->progressBar->getMessage());
    $this->progressBar->finish();
  }

  /**
   * Returns spinner refresh interval.
   *
   * @return float
   */
  public function interval(): float {
    return 0.1;
  }

}
