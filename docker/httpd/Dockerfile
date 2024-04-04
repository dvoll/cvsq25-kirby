# MIT License

# Copyright (c) 2023 Fork Unstable Media GmbH

# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:

# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.


FROM httpd:alpine3.17

COPY app.conf /usr/local/apache2/conf/app.conf

RUN printf "Include conf/app.conf\n" >> /usr/local/apache2/conf/httpd.conf
