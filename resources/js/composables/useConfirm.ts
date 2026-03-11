import Swal from 'sweetalert2'

type ConfirmOptions = {
    title?: string
    text?: string
    confirmButtonText?: string
    cancelButtonText?: string
    icon?: 'warning' | 'error' | 'question' | 'info' | 'success'
}

export async function useConfirm(options: ConfirmOptions = {}) {
    const result = await Swal.fire({
        title: options.title ?? 'Tem certeza?',
        text: options.text ?? 'Essa ação não poderá ser desfeita.',
        icon: options.icon ?? 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText ?? 'Sim, continuar',
        cancelButtonText: options.cancelButtonText ?? 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-2xl',
            title: 'text-lg font-semibold',
            htmlContainer: 'text-sm text-muted-foreground',
            actions: 'gap-3',
            confirmButton:
                'inline-flex items-center justify-center rounded-lg bg-destructive px-4 py-2 text-sm font-medium text-white transition hover:opacity-90',
            cancelButton:
                'inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted',
        },
    })

    return result.isConfirmed
}
