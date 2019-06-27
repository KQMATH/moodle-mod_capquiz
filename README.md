# CAPQuiz
[![Build Status](https://travis-ci.org/KQMATH/moodle-mod_capquiz.svg?branch=master)](https://travis-ci.org/KQMATH/moodle-mod_capquiz)

Computer adaptive practice activity module for Moodle

## What is it?

CAP is short for /Computer Adaptive Practice/, a term coined by [Klinkenberg, Straatemeier, and van der Maas (2011)](https://www.sciencedirect.com/science/article/pii/S0360131511000418). Where most LMS quiz systems give the students a fixed sequence of questions regardless of how well the students answer, a CAP system will estimate student ability based on their answers, and try to find questions at the right level of difficulty.

In CAPQuiz, the proficiency is measured by a rating.  Good answers increase the rating, and bad answers decrease it.  To increase the rating, students need to give good answers more of than bad ones /over time/.  We have used CAPQuiz as a mandatory assignment, where the students have to reach a certain rating in order to be allowed to sit the exam.

Estimating question difficulty is known to be difficult. CAPQuiz automates this process to some extent. The question author must provide an initial estimate, but CAPQuiz improves the estimates based by comparing how the same student answers different questions. Hence the rated question sets will improve over time.

## Documentation
Documentation is available [here](https://github.com/KQMATH/moodle-mod_capquiz/wiki), including [installation instructions](https://github.com/KQMATH/moodle-mod_capquiz/wiki/Installation-instructions).

## History:
The idea of an adaptive learning system at NTNU in Ålesund (then Ålesund University College) was first conceived by Siebe van Albada.  His efforts led to a prototype, known as [MathGen](https://github.com/MathGen/oppgavegenerator), written as a standalone server in python.

The first prototype was tested by several lecturers, and was well received by students. There were, however, many problems which we lacked the resources to handle. Most of these problems had already been solved by Moodle and the STACK question type, and it made sense to reimplement the adaptive quiz functionality in Moodle to take advantage of this.

## Credits:
CAPQuiz includes the work of many [contributors](https://github.com/KQMATH/moodle-mod_capquiz/wiki/Credits).

**Project lead:** Hans Georg Schaathun: <hasc@ntnu.no>

**Developers:**
* Aleksander Skrede <aleksander.skrede@gmail.com>
* Sebastian S. Gundersen <sebastian@sgundersen.com>
* [André Storhaug](https://github.com/andstor) <andr3.storhaug@gmail.com>

**Original idea:**
Siebe Bruno Van Albada <siebe.b.v.albada@ntnu.no>

The first prototype was funded in part by
[Norgesuniversitetet](https://norgesuniversitetet.no/).

The development of CAPQuiz has been funded in part by internal grants from Ålesund University College and NTNU Toppundervisning at [NTNU - Norwegian University of Science and Technology](http://www.ntnu.no).
