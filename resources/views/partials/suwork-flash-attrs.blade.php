data-suwork-flash-success="{{ session('success') ? '1' : '0' }}"
data-suwork-flash-danger="{{ session('error') ? '1' : '0' }}"
data-suwork-flash-warning="{{ session('warning') ? '1' : '0' }}"
data-suwork-flash-info="{{ (session('info') || session('status')) ? '1' : '0' }}"
