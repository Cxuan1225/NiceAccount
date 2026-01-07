import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import Swal from 'sweetalert2';

export const notyf = new Notyf({
    duration: 3000,
    position: {
        x: 'right',
        y: 'bottom',
    },
});

export function confirmDelete(message = 'This action cannot be undone!') {
    return Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
    });
}

export function confirmReverse(
    message = 'This will create a reversing journal entry.',
) {
    return Swal.fire({
        title: 'Reverse journal entry?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reverse it',
        cancelButtonText: 'Cancel',
    });
}
