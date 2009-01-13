#!/usr/bin/python2.4
#
# Copyright 2009 Google Inc. All Rights Reserved.

"""Install script for the Google Checkout OS Commerce plugin.

"""

__author__ = 'alek.dembowski@gmail.com (Alek Dembowski)'

import filecmp
import logging
import optparse
import os
import shutil
import StringIO
import subprocess


MERGE_MARKER = '<<<<<<<'


def install_file(diff3, plugin, golden, destination):
  '''Installs the plugin file to the destination directory.

  For each file, if it only exists in the plugin, or is identical to the
  install we pass, We only do the diff it it exists in all three places
  Plugin           Install                Result
    Changed          Same                   Merge
                     Changed                Merge
                     Deleted                Warn
    New              New                    Okay IFF contents match
                     Doesn't Exist          Copy

  Expects the path to the plugin's changed file, the golden file and the
  destination file.

  Straight copy if it doesn't exist in the destination, merge if it does
  exist.

  Returns True iff there are no conflicts or warnings.

  TODO(alek.dembowski) This does not do any error checking... at a minimum we
  should be checking that the subprocess completed properly, that the
  destination file is writable, and catching IO exceptions during the write.

  TODO(alek.dembowski) Need to fix the file permissions on the files we modify
  and copy... The directories need to be o+rx and the php files need to be o+r
  '''
  logging.info('Installing %s to %s based on %s' % (plugin, destination, golden))

  if os.path.isdir(plugin):
    return True

  if not os.path.exists(destination):
    make_dir(os.path.dirname(destination))
    shutil.copy(plugin, destination)
    os.chmod(destination, 0664)

    return os.path.exists(golden)
  elif not os.path.exists(golden):
    # Touchy here... we should only be okay with this case if we know the files
    # are the same...
    if filecmp.cmp(plugin, destination):
      return True
    else:
      print('%s unexpectedly exists in the installation directory. '
            'This may mean that the plugin has been installed once '
            'before already. ' % destination)
      return False

  # Apply the merge to the destination file
  output = merge(diff3, plugin, golden, destination)

  # Open dest file and write the buffer to it
  dest = open(destination, mode='w')
  dest.write(output)
  dest.close()
  os.chmod(destination, 0664)

  # Test for merge markers
  if output.find(MERGE_MARKER) >= 0:
    print('%s had some conflicts during the merge, please check the '
          'file file for merge markers and resolve these conflicts' %
          destination)
    return False

  return True


def merge(diff3, plugin, golden, destination):
  logging.info('Running %s on the three input files, %s, %s, and %s' %
               (diff3, plugin, golden, destination))
  merger = subprocess.Popen([diff3, '-m', plugin, golden, destination],
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
  (out, error) = merger.communicate()

  return out


def remove_left(original, removed):
  if original.find(removed) >= 0:
    size = len(removed)
    return original[size:]

  return original


def list_files(root):
  files = []

  for dirpath, dirnames, filenames in os.walk(root):
    if dirpath.find('.svn') < 0:
      # Strip the root from the path so we have the relative dir
      relative_path = remove_left(dirpath, root)

      for name in filenames:
        files.append(os.path.join(relative_path, name))

  return files


def install(diff3, plugin, golden, install):
  plugin_files = list_files(plugin)

  for file in plugin_files:
      install_file(diff3, os.path.join(plugin, file),
                   os.path.join(golden, file), os.path.join(install, file))


def make_dir(path):
  if not os.path.exists(path):
    path = path.rstrip('/')
    make_dir(os.path.dirname(path))
    os.mkdir(path)
    os.chmod(path, 0755)


def backup(install_dir, backup_dir):
  if os.path.exists(backup_dir):
    shutil.rmtree(backup_dir)

  make_dir(os.path.dirname(backup_dir.rstrip('/')))
  shutil.copytree(install_dir, backup_dir)


def rollback(install_dir, backup_dir):
  if os.path.exists(backup_dir) and os.path.isdir(backup_dir):
    shutil.rmtree(install_dir)
    shutil.copytree(backup_dir, install_dir)


def main():
  p = optparse.OptionParser()
  p.add_option('-q', '--quiet', action='store_true')
  p.add_option('-d', '--diff3', action='store', default='diff3',
               help='Path to the diff3 executable. Assumes diff3 '
                    'exists on the system path otherwise.')
  p.add_option('-b', '--backupdir', action='store',
               help='The directory to store the backup of the current install')
  p.add_option('-r', '--restore', action='store_true',
               help='Restores the installation from the specified directory')

  options, args =  p.parse_args()
  if not options.quiet:
    logging.getLogger().setLevel(logging.ERROR)
  else:
    logging.getLogger().setLevel(logging.DEBUG)
    if options.diff3:
      logging.info('Diff3 option passed: %s' % options.diff3)

  if options.restore:
    if len(args) > 2:
      logging.error('Too many arguments for restoring from a backup, expected '
                    '<installation directory> <backup directory>')
    elif len(args) < 2:
      logging.error('Too few arguments, expected '
                    '<installation directory> <backup directory>')
    else:
      rollback(args[0], args[1])
  else:
    # Check that we have the args we're expecting
    if len(args) < 3:
      print ('Not enough arguments supplied, expects '
             '<checkout plugin> <clean oscommerce> <existing oscommerce install>')
      return
    elif len(args) > 3:
      print ('Too many args provided, expects '
             '<checkout plugin> <clean oscommerce> <existing oscommerce install>')
      return

    print('Beginning install process')

    plugin_dir = args[0]
    golden_dir = args[1]
    install_dir = args[2]
    if options.backupdir:
      logging.info(' Backing up directory first')
      backup_dir = os.path.realpath(options.backupdir)
      backup(install_dir, backup_dir)

    install(options.diff3, plugin_dir, golden_dir, install_dir)


if __name__ == '__main__':
  main()
