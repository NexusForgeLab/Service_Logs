<?php
declare(strict_types=1);

/**
 * Enhanced SimplePDF writer.
 * Now supports font sizes, bold, and absolute positioning.
 */
class SimplePDF {
  private array $ops = [];
  private int $y;
  private int $pageHeight = 842;
  private int $pageWidth = 595;

  public function __construct() {
    $this->y = $this->pageHeight - 50; // Start position
  }

  // Add text at current cursor
  public function addText(string $text, int $size=12, bool $bold=false): void {
    $this->ops[] = [
        'type' => 'text', 
        'text' => $text, 
        'size' => $size, 
        'bold' => $bold, 
        'x' => 40, 
        'y' => $this->y
    ];
    $this->y -= ($size + 6); // Auto line break
  }

  // Add centered text
  public function addCenter(string $text, int $size=14, bool $bold=true): void {
    // Approx char width for Helvetica is ~0.5 * fontSize
    $textWidth = strlen($text) * ($size * 0.4); 
    $x = ($this->pageWidth - $textWidth) / 2;
    
    $this->ops[] = [
        'type' => 'text',
        'text' => $text,
        'size' => $size,
        'bold' => $bold,
        'x' => $x,
        'y' => $this->y
    ];
    $this->y -= ($size + 10);
  }

  // Add a horizontal line
  public function addLine(): void {
    $this->y -= 5;
    $this->ops[] = ['type' => 'line', 'y' => $this->y];
    $this->y -= 15;
  }

  // Add some vertical space
  public function addSpace(int $pts = 10): void {
    $this->y -= $pts;
  }

  private function esc(string $s): string {
    $s = str_replace("\\", "\\\\", $s);
    $s = str_replace("(", "\\(", $s);
    $s = str_replace(")", "\\)", $s);
    return $s;
  }

  public function output(string $filename = "document.pdf"): void {
    // Generate Page Content Stream
    $content = "";
    
    // We will use 2 fonts: F1=Helvetica, F2=Helvetica-Bold
    foreach ($this->ops as $op) {
      if ($op['type'] === 'text') {
        $font = $op['bold'] ? '/F2' : '/F1';
        $safe = $this->esc($op['text']);
        $content .= "BT {$font} {$op['size']} Tf {$op['x']} {$op['y']} Td ({$safe}) Tj ET\n";
      } 
      elseif ($op['type'] === 'line') {
        $content .= "1 w 40 {$op['y']} m ".($this->pageWidth-40)." {$op['y']} l S\n";
      }
    }

    // Build PDF objects
    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>"; // 1
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>"; // 2
    
    // Page Object linking to Fonts (F1, F2)
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>"; // 3
    
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"; // 4 (F1)
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>"; // 5 (F2)
    
    $objects[] = "<< /Length ".strlen($content)." >>\nstream\n".$content."endstream"; // 6

    // Write File
    $pdf = "%PDF-1.4\n";
    $xref = [];
    $pos = strlen($pdf);

    for ($i=0; $i<count($objects); $i++){
      $xref[$i+1] = $pos;
      $obj = ($i+1)." 0 obj\n".$objects[$i]."\nendobj\n";
      $pdf .= $obj;
      $pos = strlen($pdf);
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 ".(count($objects)+1)."\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i=1; $i<=count($objects); $i++){
      $pdf .= str_pad((string)$xref[$i], 10, "0", STR_PAD_LEFT)." 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n".$xrefPos."\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.strlen($pdf));
    echo $pdf;
  }
}