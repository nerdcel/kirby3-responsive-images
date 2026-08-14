/**
 * Picks a black or white backdrop colour depending on the perceived
 * brightness of the given hex font colour, so the semi-transparent
 * background always provides good contrast against the text (a light
 * font gets a dark backdrop, a dark font gets a light backdrop).
 *
 * Mirrors ResponsiveImages::contrastBackdropRgb() on the PHP side - keep
 * both in sync.
 */
export function contrastBackdropRgb(color) {
  let hex = String(color || '').replace('#', '');

  if (hex.length === 3) {
    hex = hex.split('').map((c) => c + c).join('');
  }

  if (hex.length !== 6 || /[^0-9a-f]/i.test(hex)) {
    return [0, 0, 0];
  }

  const r = parseInt(hex.slice(0, 2), 16);
  const g = parseInt(hex.slice(2, 4), 16);
  const b = parseInt(hex.slice(4, 6), 16);

  // Perceived brightness (ITU-R BT.601 luma)
  const brightness = (r * 299 + g * 587 + b * 114) / 1000;

  return brightness > 140 ? [0, 0, 0] : [255, 255, 255];
}

export function contrastBackdropColor(color, alpha = 0.45) {
  const [r, g, b] = contrastBackdropRgb(color);

  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
