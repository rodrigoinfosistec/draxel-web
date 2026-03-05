import { usePage } from '@inertiajs/vue3'
import { watchEffect } from 'vue'

type CurrentCompany = {
    id: number
    name: string
    color: string | null
} | null

function hexToRgb(hex: string) {
    const clean = hex.replace('#', '')
    const bigint = parseInt(clean, 16)
    const r = (bigint >> 16) & 255
    const g = (bigint >> 8) & 255
    const b = bigint & 255
    return { r, g, b }
}

export function useCompanyTheme() {
    const page = usePage<{ currentCompany: CurrentCompany }>()

    watchEffect(() => {
        const color = page.props.currentCompany?.color
        const root = document.documentElement

        if (!color) {
            root.style.removeProperty('--company-color')
            root.style.removeProperty('--company-color-soft')
            return
        }

        const { r, g, b } = hexToRgb(color)

        root.style.setProperty('--company-color', color)
        root.style.setProperty('--company-color-soft', `rgba(${r}, ${g}, ${b}, 0.12)`)
    })
}
