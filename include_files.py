import sublime, sublime_plugin, re, sys, os
sys.path.append(os.path.join(os.path.dirname(__file__)))
import my

regions = {}

def normalizePath(base_path, rel_path):
  if not os.path.isabs(rel_path):
    rel_path = os.path.join(base_path, rel_path).replace("\\","/")
  return rel_path

def prepareContent(path, line_ending, indent = ""):
  with open(path, 'r') as content_file:
    content = content_file.read()
    content = re.sub(r"(^|\n\r?)", r'\1' + indent, content)
    content = line_ending + content.rstrip() + line_ending
  return content

def fillRegion(sublime, view, edit, region, replace_string):
  if region.__class__.__name__ == "Region":
    size_before = region.size()
    before_replace_str = view.substr(region)
    if before_replace_str != replace_string:
      view.replace(edit, region, replace_string)
    return sublime.Region(region.b, region.b + (len(replace_string) - size_before))
  elif isinstance(region, int):
    if len(replace_string) > 0:
      view.insert(edit, region, replace_string)
    return sublime.Region(region, region + len(replace_string))

def pointAdjust(point, diff_region):
  if point >= diff_region.a:
    point += diff_region.b - diff_region.a
  return point

def regionAdjust(region, diff_region):
  region.a = pointAdjust(region.a, diff_region)
  region.b = pointAdjust(region.b, diff_region)
  return region

class includeFilesCommand(sublime_plugin.TextCommand):
  def run(self, edit):
    start_pos = 0;
    while start_pos < self.view.size():
      line_ending = self.view.line_endings()
      if line_ending == 'Unix':
        line_ending = "\n"
      base_file_name = self.view.file_name()
      base_file_path = os.path.dirname(base_file_name) + os.sep
      include_start_regex = r'([\t ]*)#[\t ]*include_start:(.*)?$'
      regions['include_start_line'] = self.view.find(include_start_regex, start_pos)
      if regions['include_start_line'].a == -1:
        break
      include_start_line_str = self.view.substr(regions['include_start_line'])
      indent = re.search(include_start_regex, include_start_line_str).group(1)
      include_path = re.search(include_start_regex, include_start_line_str).group(2).strip()
      include_end_regex = r'([\t ]*)#[\t ]*include_end: *' + re.escape(include_path) + r' *$'
      regions['include_end_line'] = self.view.find(include_end_regex, start_pos)
      include_path_normalized = normalizePath(base_file_path, include_path)
      include_content = prepareContent(include_path_normalized, line_ending, indent)
      regions['insert'] = sublime.Region(regions['include_start_line'].b, regions['include_end_line'].a)
      regions['insert_diff'] = fillRegion(sublime, self.view, edit, regions['insert'], include_content)
      regionAdjust(regions['insert'], regions['insert_diff'])
      regionAdjust(regions['include_end_line'], regions['insert_diff'])
      regions['include_end_line_indent'] = self.view.find(r'[\t ]*', regions['include_end_line'].a)
      regions['insert_end_line_indent_diff'] = fillRegion(sublime, self.view, edit, regions['include_end_line_indent'], indent)
      regionAdjust(regions['include_end_line'], regions['insert_end_line_indent_diff'])
      # self.view.sel().add(regions['include_end_line'])
      regions['fold'] = sublime.Region(regions['insert'].a, regions['include_end_line'].b)
      self.view.fold(regions['fold'])
      start_pos = regions['fold'].b;

  # include_start: my2/my_file.py
  # print("test")
  # print("test")
  # print("test")
  # print("test")
  # include_end: my2/my_file.py
      # include_start: my2/my_file.py
      # print("test")
      # print("test")
      # print("test")
      # print("test")
      # include_end: my2/my_file.py
# include_start: my2/my_file.py
# print("test")
# print("test")
# print("test")
# print("test")
# include_end: my2/my_file.py
# include_start: my2/my_file.py
# print("test")
# print("test")
# print("test")
# print("test")
# include_end: my2/my_file.py



# some other comment

def my_other_func():
  pass