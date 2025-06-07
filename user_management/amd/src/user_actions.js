export const init = () => {
    document.querySelectorAll('.action-button').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            const isVisible = dropdown.classList.contains('show-dropdown');

            document.querySelectorAll('.action-dropdown').forEach(d => d.classList.remove('show-dropdown'));

            if (!isVisible) {
                dropdown.classList.add('show-dropdown');
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.action-menu')) {
            document.querySelectorAll('.action-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show-dropdown');
            });
        }
    });

    document.querySelectorAll('.change-role').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            if (confirm("Are you sure you want to change this user's role?")) {
                console.log('Change role for user:', userId);
                // TODO: Implement AJAX request to change role
            }
        });
    });

    document.querySelectorAll('.delete-user').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            if (confirm("Are you sure you want to delete this user?")) {
                console.log('Delete user:', userId);
                // TODO: Implement AJAX request to delete user
            }
        });
    });
};
