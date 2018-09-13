# CAPQuiz

## What is it?

CAP is short for /Computer Adaptive Practice/, a term coined by [Klinkenberg, Straatemeier, and van der Maas](https://www.sciencedirect.com/science/article/pii/S0360131511000418). Where most LMS quiz systems give the students a fixed sequence of questions regardless of how well the students answer, a CAP system will estimate student ability based on their answers, and try to find questions at the right level of difficulty.

In CAPQuiz, the proficiency is measured by a rating.  Good answers increase the rating, and bad answers decrease it.  To increase the rating, students need to give good answers more of than bad ones /over time/.  We have used CAPQuiz as a mandatory assignment, where the students have to reach a certain rating in order to be allowed to sit the exam.

Estimating question difficulty is known to be difficult. CAPQuiz automates this process to some extent. The question author must provide an initial estimate, but CAPQuiz improves the estimates based by comparing how the same student answers different questions. Hence the rated question sets will improve over time.

**User Documentation:** http://confluence.uials.no:8090/display/KQMATHPUB/CAPQuiz

## Installation:

### Moodle plugins directory

CAPQuiz has not yet been published in the Moodle plugins directory.
When it is, the following instruction will be valid.

Click on **Install now** within the plugins directory, and then select your site from the list of "My sites"

### Manually
Unzip all the files into a temporary directory.
Copy the **capquiz** folder into **moodle/mod**.
The system administrator should then log in to moodle and click on the **Notifications** link in the Site administration
block.

## Uninstalling:
Delete the module from the **Activities** module list in the admin section.

## History:
The idea of an adaptive learning system at NTNU in Ålesund (then Ålesund University College) was first conceived by Siebe van Albada.  His efforts led to a prototype, known as [MathGen](https://github.com/MathGen/oppgavegenerator), written as a standalone server in python.

The first prototype was tested by several lecturers, and was well received by students. There were, however, many problems which we lacked the resources to handle. Most of these problems had already been solved by Moodle and the STACK question type, and it made sense to reimplement the adaptive quiz functionality in Moodle to take advantage of this.

## Credits:
**Project lead:** Hans Georg Schaathun: <hasc@ntnu.no>

**Developers:**
Aleksander Skrede <aleksander.skrede@protonmail.com>,
Sebastian S. Gundersen <sebastsg@stud.ntnu.no>,
André Storhaug <andstorh@stud.ntnu.no>

**Original idea:**
Siebe Bruno Van Albada <siebe.b.v.albada@ntnu.no>
