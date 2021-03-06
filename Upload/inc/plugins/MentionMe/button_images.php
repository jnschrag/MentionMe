<?php
/*
 * Plugin Name: MentionMe for MyBB 1.6.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains the postbit buttons for mention_install.php
 */

$postbit_button = <<<EOF
R0lGODlhHwAUAOf2AEh0owJrrwJqrgJssQNWiwJpqwNVigJnqQNXjgJlpgNZkIilxAJkowNbkwNgnANclgJnqANThwNemQJioAJknhvSGwJloQNaiwJlmwNcjgNShgNcjANXjQJhoQJcmgqDFhWJIlOo/vDy+Zvg/gJkpA2FGanT8wRzQIb5hvLz+EyGugFxOgWDDQNThjLVMgOCCwOFCA9Hf1Sl8c2YWgJXkeji5rPm+3nI/hVUlUCJ4i3TLZXX/gM/ad3e6yVq2Nfe6YzP/QWIB2yj4RJ0su3r9S9p0Onr9x1f19rg8+jd0ZLb/fn4/DB8tEGi3ARNgijSKAZLfPzgrMv+/gqTC/qWEgiEFZfT+VOW4fv2+OrSdAWAEYOy3t3Oyarm/oOk1gNUiIigzgJYk/7RBQhssB6QJBKMEyJdyI23uAJCbpxlMoFZOQY+b0lyubOVi02Q8b3g8iNt4AN/D/Dt8/7cSCKEzMr1/kDYQAJblARlKyBiwC7SLm+9/t63jAJsMwpCdSFoohdFZQJprfXy9v69CSVDWQlDcrvR7fbz903fTQSDECHQIRzOHBSWFQNOf0feRyPRI1Cm/guCGFTnVCB9xAU/aNH4/rHn/uTg52aCZxtqy+7aewiBFQNzP/n19+fYyxl8vfjy8jt4vdXM0q7t/gtxts/4/rXG4AJenLXN6P35+xyZHQeICcy+xE/hT/Lx8wtM2BCIGkyezwNQgXOx8zqB0UOT8NK2Gz+P6QJHdSl3tANUh/7OIAtsqWnvaWiR1wRKgvbw84fV/kveSw+EHBtn1wRwOnTF/hKVEzbUNgJdmBiOGhTQFD/aP/z7/SXRJRBT1tzn82vvaxtfm0KZ+FKr/gSGCTjWOASAEL7F3nrQ9wFuNgNMe+3t9f7eaAFsM/naNkTcRHaz7dfj8ECRwkxneU19dwJemf7SYbXa87iwOlmh/izTLBqMI1PiUxCEHSbXJv22NXyHn2KQ0xmOHzWFvV3lXQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAP8ALAAAAAAfABQAAAj+AP8JHEiwoMGDCBMqTLgAgMOHECNKjLhAYMMBGDNq3MhxI4CKAAKIHEmypMmTAP4BEMCypYBmMGG6nElTQEoABXLmhFmAkE9CzXQKHarz5oGjB5od8In0qNKmEE6QmccJAtKbCbImaEZoa8xmWcEmoJDAAjsU0WBZ0HqTgdtmDIDG9cmVQV0KFDC4q9cqEgYKbm9OGMx1QmHChAwnLgZimCpE4BhtqrJiwk0HmLk60JyZ0GbPynq1E2bHmot171Y5uCmhNVcJMFvDJjRbQhlJjpgh0/NEUYUgEm4+GP6g2fCvMR8QeoCnxIdjOpxNuZZI24ObDbI34Jr9p/fsGTJObNDyaFGcDRmy31TAnj3M9vDjX2BRYdmLC+1vItjPH+hX/gBy0Ec1MHjDAX83EaDgggp6RwiDEEaYUkMGVGjhhRhmiOFHFk3k4YcOVRQQADs=
EOF;

$postbit_button_on = <<<EOF
R0lGODlhHwAUAOfXAKcEANgwUNgyU9ctTNkzVssOEcsMDdYrR8wQFdUoQs0TGtQlPM4VH8BjZNEfMM8YJdAcKtMiNsD/wA94A5MiOAp8AiyNJ7Pm+4igzsYlP7Hn/jhhveji5u7ae9CXWYoPF3nI/rX/tXTF/sQOEd3e6zXYNZfT+e3t9eFhMm+9/vn199zn82NFbtrg80ReFfz7/R1f17kgN/n4/JcOFcv+/sUMDarm/v7RBadSeJUfNpH5kbASHFZurmKh6rJMTr4TGklyufDy+d3OyanT8423uPLx8yJdyA3JDQDKAC9p0ALFAm8aMy5q29QyVNfj8LsUHv7cSKKpz/OuDUOT8HmPtr4+ZhCDC7shOLonQtUrR02Q8cy+xM0mQd1WOP7OIBWFD3yHn0aM5Gyj4f61NdXM0uSkJ8c2V7YXI/Xy9rn/ub3g8mh0ofDt82VikSaRJAtM2HUcL4lnnefYy8IPFAGaAfbz9/Lz+FOo/iiMI48fPJkiP5MkJ9H4/skMDSF/FHOx88ovTvuWEYzP/dfe6TzZPOnr9/jy8ofV/tIqRn0THbXG4N63jJvg/tUvT67t/v69Cfbw85MjL4SNurpAXbXa8xBT1ihi0Pv2+P7eaLJMJFKr/qMQF8IkP8JaIVvmW78QFQnQCSLRIo1ek0p7xwaGBCpZF7xmhRZ6CeTg5yqMJBN6BitmCACZAGiR16D8oLwNELjM5ieMI/35+7vR7VOW4SNtCWQjQYjM8FCm/scSGfzgrMr1/kxAdPvObSVq2JPa/B6IGNk1UGKQ026BqCqNJcQiOVmh/mzrbGVQeXaz7e3r9b7F3ujd0ctPa0KZ+PnaNhzQHECJ4hF4BZXX/lYvVc/4/iOEGwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAP8ALAAAAAAfABQAAAj+AP8JHEiwoMGDCBMqTNgAgMOHECNKjNhAYEMCGDNq3MhxI4CKAASIHEmypMmTAP4BCMCyZYBFMGG6nEkzQEoAA3LmhDkA4iKdQIPqvHmg6IFFBxwaLYp0qdOlNxNITbAIANWYi6Rmncq1680FYBctACD2YdUFZ8GqXQv2ZoS3VSPEhQtAbl0XbvAQswDsixVStSLcdEC4qgPDhQEcVpxKQpoQOjyVgAaKjoObEDJXhQAzM2cAnyHEkuDqGKFQR5QgYQXh5oPXDxa9xhrzAYAHpaz5OaVK2oQJFVbZVsmgOIOqxScaX86cwU0F0KHDjE69uvXoNxFo304W6/bv4L8e3yxAvjz5iObTq0/Z0ID79/Djy4//0eLE+/gdVgwIADs=
EOF;

$postbit_button = base64_decode($postbit_button);
$postbit_button_on = base64_decode($postbit_button_on);

?>
