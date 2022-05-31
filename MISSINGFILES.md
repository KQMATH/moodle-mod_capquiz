
# Files to implement for 3d Radio question subtype
-------------------------------------------------
(Found by using git diff on mooodle-qtype-stack repo between feat/3d-vis and master-branch)

# amd/src
- marchingcube.js
- mathbox-bundle.js
- parser.js
- threedradio.js
- triangletable.js

# amd/build
- parser.min.js
- threedradio.min.js.map
(Check other files in this dict also)

# lang/en
- qtype_stack.php

# stack
#   /threedradio
    - threedradio.class.php
#   / (somewhere)
    - mathbox.block.js

# main
- thirdpartylibs.xml            #
( See library tag "JSXGraph" and "MathBox)