export type DotStyle =
    | 'square'
    | 'dots'
    | 'rounded'
    | 'classy'
    | 'classy-rounded'
    | 'extra-rounded'

export type CornerSquareStyle = 'dot' | 'square' | 'extra-rounded'

export type CornerDotStyle = 'dot' | 'square'

export type GradientType = 'linear' | 'radial'

export type FrameType = 'none' | 'simple' | 'rounded' | 'speech-bubble'

export interface QrVisualSettings {
    dotStyle: DotStyle
    dotColor: string
    bgColor: string
    cornerSquareStyle: CornerSquareStyle
    cornerDotStyle: CornerDotStyle
    gradientEnabled: boolean
    gradientType: GradientType
    gradientColorStart: string
    gradientColorEnd: string
    gradientRotation: number
    logoPath: string | null
    logoMargin: number
    logoSize: number
    logoHideBackground: boolean
    frame: FrameType
    frameText: string
    frameColor: string
}

export const DEFAULT_VISUAL_SETTINGS: QrVisualSettings = {
    dotStyle: 'square',
    dotColor: '#000000',
    bgColor: '#ffffff',
    cornerSquareStyle: 'square',
    cornerDotStyle: 'square',
    gradientEnabled: false,
    gradientType: 'linear',
    gradientColorStart: '#000000',
    gradientColorEnd: '#444444',
    gradientRotation: 0,
    logoPath: null,
    logoMargin: 5,
    logoSize: 0.3,
    logoHideBackground: false,
    frame: 'none',
    frameText: '',
    frameColor: '#000000',
}

export const DOT_STYLES: { value: DotStyle; label: string }[] = [
    { value: 'square', label: 'Square' },
    { value: 'dots', label: 'Dots' },
    { value: 'rounded', label: 'Rounded' },
    { value: 'classy', label: 'Classy' },
    { value: 'classy-rounded', label: 'Classy Rounded' },
    { value: 'extra-rounded', label: 'Extra Rounded' },
]

export const CORNER_SQUARE_STYLES: { value: CornerSquareStyle; label: string }[] = [
    { value: 'square', label: 'Square' },
    { value: 'dot', label: 'Dot' },
    { value: 'extra-rounded', label: 'Extra Rounded' },
]

export const CORNER_DOT_STYLES: { value: CornerDotStyle; label: string }[] = [
    { value: 'square', label: 'Square' },
    { value: 'dot', label: 'Dot' },
]

export const FRAME_TYPES: { value: FrameType; label: string }[] = [
    { value: 'none', label: 'None' },
    { value: 'simple', label: 'Simple' },
    { value: 'rounded', label: 'Rounded' },
    { value: 'speech-bubble', label: 'Speech Bubble' },
]

export const BRAND_PALETTES: { name: string; dotColor: string; bgColor: string }[] = [
    { name: 'Classic', dotColor: '#000000', bgColor: '#ffffff' },
    { name: 'Navy', dotColor: '#1e3a5f', bgColor: '#ffffff' },
    { name: 'Forest', dotColor: '#166534', bgColor: '#f0fdf4' },
    { name: 'Crimson', dotColor: '#991b1b', bgColor: '#fff1f2' },
    { name: 'Violet', dotColor: '#5b21b6', bgColor: '#f5f3ff' },
    { name: 'Midnight', dotColor: '#ffffff', bgColor: '#0f172a' },
    { name: 'Ocean', dotColor: '#0c4a6e', bgColor: '#e0f2fe' },
    { name: 'Sunset', dotColor: '#7c2d12', bgColor: '#fff7ed' },
]
