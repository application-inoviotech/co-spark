<script setup>
defineProps({ name: String });

const paths = {
  dashboard: 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
  scan: 'M3 8V3h5m8 0h5v5m0 8v5h-5m-8 0H3v-5M3 12h18',
  reports: 'M4 20h16M7 16v-4m5 4V8m5 8V4',
  settings: 'M4 6h16M4 12h16M4 18h16M8 4v4m8 2v4m-6 2v4',
  user: 'M2 20c1-3 4-5 7-5s6 2 7 5M12.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0ZM16 5a3.5 3.5 0 0 1 0 7m2 3c2 1 3 3 4 5',
  membership: 'M3 5h18v14H3zM12 10h5m-5 4h5M7 9v4m-2 3h4',
  policy: 'M12 3 4.5 6v6c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6zM9 12l2 2 4-4',
  claim: 'M6 3h8l4 4v14H6zM14 3v5h4M12 11v3m0 3h.01',
  lead: 'M3 4h18v13H9l-5 4v-4H3zM7 9h10m-10 4h6',
  payment: 'M3 5h18v14H3zM3 10h18M6 15h4',
  'token-transaction': 'M3 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM3 7l13-3v3m1 7h.01',
  'token-transfer': 'M4 8h16l-4-4M20 16H4l4 4',
  'producer-application': 'M8 5H5v16h14V5h-3M8 3h8v4H8zM8 14l2 2 5-5',
  producer: 'M3 7h18v13H3zM8 7V4h8v3M3 12l9 3 9-3',
  commission: 'M12 3a9 9 0 1 0 9 9h-9zM15 3.5V9h5.5A9 9 0 0 0 15 3.5z',
  'vacation-request': 'M3 15l6-7 4 5 3-3 5 5v5H3zM17 5h.01',
  partner: 'M3 21V8l9-5 9 5v13zM9 21v-5h6v5M8 11h1m6 0h1',
  'qr-code': 'M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h3v3h3v3h-6z',
  appointment: 'M3 5h18v16H3zM3 10h18M8 3v4m8-4v4M8 15h2m4 0h2',
  'contact-message': 'M3 5h18v14H3zM3 6l9 7 9-7',
  page: 'M6 3h8l4 4v14H6zM14 3v5h4M9 13h6m-6 4h6',
  'membership-plan': 'M3 6h18v12H3zM3 10h18M7 14h3m4 0h3',
  'insurance-product': 'M12 3 4.5 6v6c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6zM12 9v6m-3-3h6',
  folder: 'M3 6h7l2 2h9v12H3z',
  article: 'M4 4h16v16H4zM8 8h8m-8 4h8m-8 4h5',
  clock: 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM12 7v5l3 2',
  refund: 'M4 10h12a4 4 0 0 1 0 8h-5M8 6l-4 4 4 4',
  search: 'M16 10a6 6 0 1 1-12 0 6 6 0 0 1 12 0Zm-1.75 4.25L20 20',
  download: 'M12 3v12m-4-4 4 4 4-4M5 16v4h14v-4',
  plus: 'M12 5v14M5 12h14',
  chevron: 'm6 9 6 6 6-6',
  check: 'm5 12 4 4L19 6',
  back: 'M20 12H4m6-6-6 6 6 6',
  edit: 'm15 5 4 4M4 20l1-5L16 4a2.8 2.8 0 0 1 4 4L9 19z',
  trash: 'M4 6h16M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7m4-7v7',
  blocked: 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM6 6l12 12',
  forward: 'M4 12h16m-6-6 6 6-6 6',
  close: 'm6 6 12 12M18 6 6 18',
  menu: 'M4 6h16M4 12h16M4 18h16',
  save: 'M4 3h13l3 3v15H4zM8 3v6h8V3M8 21v-7h8v7',
  stop: 'M6 6h12v12H6z',
  'sign-in': 'M15 3h5v18h-5M4 12h11m-4-4 4 4-4 4',
  'sign-out': 'M9 21H4V3h5m7 14 5-5-5-5m5 5H9',
};

const categoryIcons = {
  'insurance-category': 'folder',
  'article-category': 'folder',
  'claim-type': 'folder',
};
</script>

<template>
  <svg class="icon admin-icon" viewBox="0 0 24 24" aria-hidden="true">
    <path :d="paths[categoryIcons[name] ?? name] ?? paths.page" />
  </svg>
</template>
